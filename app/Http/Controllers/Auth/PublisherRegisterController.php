<?php

namespace App\Http\Controllers\Auth;

use App\Enums\PublisherStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PublisherRegisterRequest;
use App\Models\PublisherProfile;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewPublisherRegistered;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Services\SeoService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublisherRegisterController extends Controller
{
    public function create(Request $request, SeoService $seo): View|RedirectResponse
    {
        if ($redirect = $this->guard($request)) {
            return $redirect;
        }

        return view('auth.register-publisher', [
            'seo' => $seo->defaults('Become a publisher', 'Sell your digital products on '.setting('site_name').'.'),
            'upgrading' => $request->user() !== null,
        ]);
    }

    public function store(PublisherRegisterRequest $request, ActivityLogger $logger, NotificationService $notifications): RedirectResponse
    {
        if ($redirect = $this->guard($request)) {
            return $redirect;
        }

        $status = setting('publisher_require_approval') ? PublisherStatus::Pending : PublisherStatus::Approved;
        $existing = $request->user();

        $user = DB::transaction(function () use ($request, $status, $existing) {
            $user = $existing ?? new User($request->safe()->only(['name', 'email', 'password']));

            $user->forceFill([
                'role_id' => Role::idFor(RoleName::Publisher),
                'username' => $request->validated('username'),
                'phone' => $request->validated('phone'),
                'country' => $request->validated('country'),
                'status' => $existing?->status ?? UserStatus::Active,
            ])->save();

            $profile = new PublisherProfile($request->safe()->only(['display_name', 'website', 'bio', 'country']));
            $profile->forceFill([
                'user_id' => $user->getKey(),
                'whatsapp' => $request->validated('phone'),
                'status' => $status,
                'approved_at' => $status === PublisherStatus::Approved ? now() : null,
            ])->save();

            return $user;
        });

        if (! $existing) {
            event(new Registered($user));
            Auth::login($user);
            $request->session()->regenerate();
        }

        $user->load('publisherProfile');
        $notifications->send($notifications->staffWithPermission('manage_publishers'), new NewPublisherRegistered($user));
        $logger->log('publisher.registered', $user, "Publisher {$user->username} registered", ['status' => $status->value], $user);

        return redirect()->route('publisher.dashboard')->with('success', $status === PublisherStatus::Pending
            ? 'Your publisher account was created and is waiting for admin approval. You can complete your profile meanwhile.'
            : 'Your publisher account is ready. Start by adding your first product!');
    }

    /**
     * Who may use this form: guests (when enabled) and signed-in customers.
     */
    protected function guard(Request $request): ?RedirectResponse
    {
        if (! setting('publisher_registration_enabled')) {
            return redirect()->route('home')->with('warning', 'Publisher registration is currently closed.');
        }

        $user = $request->user();

        if ($user?->isPublisher()) {
            return redirect()->route('publisher.dashboard');
        }

        if ($user && ! $user->isCustomer()) {
            return redirect()->to($user->dashboardRoute())->with('warning', 'Staff accounts cannot register as publishers.');
        }

        return null;
    }
}
