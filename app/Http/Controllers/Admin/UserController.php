<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublisherStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\PublisherProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Customers and publishers. Staff accounts are listed read-only here and
 * managed by Super Admins in AdminUserController.
 */
class UserController extends Controller
{
    public function __construct(protected ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        return $this->listing($request, $request->query('role'));
    }

    public function customers(Request $request): View
    {
        return $this->listing($request, RoleName::Customer->value);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User, 'countries' => Countries::all()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = new User($request->safe()->only(['name', 'username', 'email', 'password', 'phone', 'country']));
            $user->forceFill([
                'role_id' => Role::idFor($request->validated('role')),
                'status' => $request->validated('status'),
                'email_verified_at' => $request->boolean('email_verified') ? now() : null,
            ])->save();

            $this->syncPublisherProfile($user);

            return $user;
        });

        $this->logger->log('user.created', $user, "User {$user->email} created");

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View|RedirectResponse
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.users.index')->with('warning', 'Staff accounts are managed under Admins.');
        }

        return view('admin.users.form', ['user' => $user, 'countries' => Countries::all()]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        DB::transaction(function () use ($request, $user) {
            $user->fill($request->safe()->only(['name', 'username', 'email', 'phone', 'country']));

            if ($request->filled('password')) {
                $user->password = $request->validated('password');
            }

            $user->forceFill([
                'role_id' => Role::idFor($request->validated('role')),
                'status' => $request->validated('status'),
                'email_verified_at' => $request->boolean('email_verified') ? ($user->email_verified_at ?? now()) : null,
            ])->save();

            $user->load('role');
            $this->syncPublisherProfile($user);
        });

        $this->logger->log('user.updated', $user, "User {$user->email} updated", ['status' => $user->status->value, 'role' => $user->role->slug]);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin() || $user->is($request->user()), 403);

        if ($user->products()->withTrashed()->exists()) {
            return back()->with('error', 'This user owns products. Suspend the account instead of deleting it.');
        }

        $user->delete();
        $this->logger->log('user.deleted', $user, "User {$user->email} deleted");

        return back()->with('success', 'User deleted.');
    }

    protected function listing(Request $request, ?string $role): View
    {
        $users = User::query()
            ->when($role, fn ($q) => $q->whereHas('role', fn ($r) => $r->where('slug', $role)))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('username', 'like', $like));
            })
            ->when(in_array($request->query('status'), ['active', 'suspended'], true), fn ($q) => $q->where('status', $request->query('status')))
            ->withCount('orders')
            ->latest('id')
            ->paginate(config('marketplace.pagination.admin'))
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'role' => $role,
            'title' => $role === RoleName::Customer->value ? 'Customers' : ($role ? RoleName::from($role)->label().'s' : 'All users'),
        ]);
    }

    /**
     * Publishers created or converted by an admin get an approved profile.
     */
    protected function syncPublisherProfile(User $user): void
    {
        if ($user->hasRole(RoleName::Publisher) && ! $user->publisherProfile()->exists()) {
            $profile = new PublisherProfile(['display_name' => $user->name, 'country' => $user->country]);
            $profile->forceFill([
                'user_id' => $user->getKey(),
                'status' => PublisherStatus::Approved,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ])->save();
        }
    }
}
