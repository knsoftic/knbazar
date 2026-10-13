<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(SeoService $seo): View
    {
        return view('auth.forgot-password', ['seo' => $seo->defaults('Forgot password')->noindex()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:150']]);

        Password::sendResetLink($request->only('email'));

        // Same response whether or not the account exists (no user enumeration).
        return back()->with('success', 'If an account exists for that email, a password reset link has been sent.');
    }
}
