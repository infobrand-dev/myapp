<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    /**
     * If the authenticated user has 2FA enabled and the current request is NOT
     * the 2FA challenge route itself, redirect them to complete the challenge.
     *
     * This middleware should be applied to all auth-protected routes.
     * It is a no-op when the user has not enabled 2FA.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->hasTwoFactorEnabled()
            && $request->session()->missing('two_factor_confirmed')
        ) {
            // Don't redirect on the 2FA challenge routes themselves
            if ($request->routeIs('two-factor.*', 'logout')) {
                return $next($request);
            }

            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
