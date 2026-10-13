<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\SeoService;
use App\Services\SlugService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(SeoService $seo): View|RedirectResponse
    {
        if (! setting('registration_enabled')) {
            return redirect()->route('login')->with('warning', 'New registrations are currently closed.');
        }

        return view('auth.register', ['seo' => $seo->defaults('Create an account')->noindex()]);
    }

    public function store(RegisterRequest $request, SlugService $slugs): RedirectResponse
    {
        abort_unless(setting('registration_enabled'), 403, 'Registration is currently closed.');

        $user = new User($request->safe()->only(['name', 'email', 'password', 'phone']));
        $user->forceFill([
            'role_id' => Role::idFor(RoleName::Customer),
            'username' => $slugs->uniqueUsername($request->input('email')),
            'status' => UserStatus::Active,
        ])->save();

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account.index'))->with('success', 'Welcome to '.setting('site_name').'! Your account is ready.');
    }
}
