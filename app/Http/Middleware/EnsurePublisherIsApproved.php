<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts product creation/submission to approved publishers. Pending
 * publishers can still sign in, see their dashboard and edit their profile.
 */
class EnsurePublisherIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->canSubmitProducts()) {
            return redirect()
                ->route('publisher.dashboard')
                ->with('warning', 'Your publisher account must be approved before you can add or submit products.');
        }

        return $next($request);
    }
}
