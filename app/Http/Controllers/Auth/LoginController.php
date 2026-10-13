<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(SeoService $seo): View
    {
        return view('auth.login', ['seo' => $seo->defaults('Sign in')->noindex()]);
    }

    public function store(LoginRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if (! $user->isActive()) {
            Auth::guard('web')->logout();
            $logger->security('Suspended account login attempt', ['user_id' => $user->getKey()]);

            return back()->withErrors(['email' => 'Your account has been suspended. Please contact support.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->saveQuietly();

        if ($user->isAdmin()) {
            $logger->log('auth.admin_login', $user, 'Admin signed in', user: $user);
        }

        return redirect()->intended($user->dashboardRoute());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
