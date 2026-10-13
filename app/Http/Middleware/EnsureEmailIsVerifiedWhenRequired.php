<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Like Laravel's "verified" middleware, but only enforced when the admin
 * enables "Require email verification" in the registration settings.
 */
class EnsureEmailIsVerifiedWhenRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && setting('email_verification_required') && ! $user->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : redirect()->guest(route('verification.notice'));
        }

        return $next($request);
    }
}
