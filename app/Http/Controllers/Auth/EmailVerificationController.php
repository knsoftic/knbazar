<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request, SeoService $seo): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->to($request->user()->dashboardRoute());
        }

        return view('auth.verify-email', ['seo' => $seo->defaults('Verify your email')->noindex()]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail() && $request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($request->user()->dashboardRoute())->with('success', 'Your email address has been verified.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->to($request->user()->dashboardRoute());
        }

        // Always send here, even if verification is optional, since the user asked for it.
        $request->user()->notify(new VerifyEmail);

        return back()->with('success', 'A new verification link has been sent to your email address.');
    }
}
