<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Super Admin only: manage Admin / Super Admin accounts and admin permissions.
 */
class AdminUserController extends Controller
{
    public function __construct(protected ActivityLogger $logger) {}

    public function index(): View
    {
        $admins = User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('slug', [RoleName::SuperAdmin->value, RoleName::Admin->value]))
            ->with('permissions:id,slug,name')
            ->orderBy('name')
            ->get();

        return view('admin.admins.index', ['admins' => $admins]);
    }

    public function create(): View
    {
        return view('admin.admins.form', ['user' => new User, 'selected' => []]);
    }

    public function store(AdminUserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = new User($request->safe()->only(['name', 'username', 'email', 'password', 'phone']));
            $user->forceFill([
                'role_id' => Role::idFor($request->validated('role')),
                'status' => $request->validated('status'),
                'email_verified_at' => now(),
            ])->save();

            $this->syncPermissions($user, $request->validated('permissions', []));

            return $user;
        });

        $this->logger->log('admin.created', $user, "Staff account {$user->email} created", ['role' => $request->validated('role')]);

        return redirect()->route('admin.admins.index')->with('success', 'Admin account created.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->isAdmin(), 404);

        return view('admin.admins.form', [
            'user' => $user,
            'selected' => $user->permissions()->pluck('slug')->all(),
        ]);
    }

    public function update(AdminUserRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        $newRole = $request->validated('role');
        $newStatus = UserStatus::from($request->validated('status'));

        if ($user->isSuperAdmin() && ($newRole !== RoleName::SuperAdmin->value || $newStatus !== UserStatus::Active) && $this->superAdminCount() <= 1) {
            return back()->withInput()->with('error', 'You cannot demote or suspend the last active Super Admin.');
        }

        DB::transaction(function () use ($request, $user, $newRole, $newStatus) {
            $user->fill($request->safe()->only(['name', 'username', 'email', 'phone']));

            if ($request->filled('password')) {
                $user->password = $request->validated('password');
            }

            $user->forceFill(['role_id' => Role::idFor($newRole), 'status' => $newStatus])->save();
            $this->syncPermissions($user, $request->validated('permissions', []));
        });

        $this->logger->log('admin.updated', $user, "Staff account {$user->email} updated", [
            'role' => $newRole,
            'permissions' => $request->validated('permissions', []),
        ]);

        return redirect()->route('admin.admins.index')->with('success', 'Admin account updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            return back()->with('error', 'You cannot delete the last Super Admin.');
        }

        $user->permissions()->detach();
        $user->delete();
        $this->logger->log('admin.deleted', $user, "Staff account {$user->email} deleted");

        return back()->with('success', 'Admin account deleted.');
    }

    /**
     * @param  array<int, string>  $slugs
     */
    protected function syncPermissions(User $user, array $slugs): void
    {
        // Super Admins implicitly hold every permission.
        $ids = $user->fresh()->isSuperAdmin() ? [] : Permission::query()->whereIn('slug', $slugs)->pluck('id')->all();
        $user->permissions()->sync($ids);
        $user->flushPermissionCache();
    }

    protected function superAdminCount(): int
    {
        return User::query()
            ->where('status', UserStatus::Active->value)
            ->whereHas('role', fn ($q) => $q->where('slug', RoleName::SuperAdmin->value))
            ->count();
    }
}
