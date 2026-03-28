<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Headers added unconditionally to every response.
     */
    private const ALWAYS = [
        // Prevent MIME-type sniffing
        'X-Content-Type-Options'  => 'nosniff',
        // Deny framing from other origins
        'X-Frame-Options'         => 'SAMEORIGIN',
        // Legacy XSS filter (IE/old Chrome)
        'X-XSS-Protection'        => '1; mode=block',
        // Control referrer leakage
        'Referrer-Policy'         => 'strict-origin-when-cross-origin',
        // Disable unused browser APIs
        'Permissions-Policy'      => 'camera=(), microphone=(), geolocation=(), payment=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::ALWAYS as $header => $value) {
            $response->headers->set($header, $value);
        }

        // HSTS — only meaningful over HTTPS, and only in non-local environments
        if ($request->isSecure() && ! app()->isLocal()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Basic Content-Security-Policy.
        // Inline scripts/styles are allowed because we use Tabler + Alpine.js inline attributes.
        // Tighten this further once you have a nonce strategy in place.
        if (! app()->isLocal()) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
                "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com data:",
                "img-src 'self' data: blob: https:",
                "connect-src 'self' wss:",
                "frame-ancestors 'self'",
            ]));
        }

        return $response;
    }
}
