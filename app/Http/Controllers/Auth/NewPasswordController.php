<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SeoService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request, SeoService $seo): View
    {
        return view('auth.reset-password', [
            'request' => $request,
            'seo' => $seo->defaults('Reset password')->noindex(),
        ]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request, $logger) {
                $user->forceFill([
                    'password' => $request->string('password')->value(),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $logger->log('auth.password_reset', $user, 'Password reset via email link', user: $user);
            }
        );

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('success', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
