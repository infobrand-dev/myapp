<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('multitenancy.mode') !== 'saas') {
            return $next($request);
        }

        $user = $request->user();

        if ($this->isPlatformAdminHost($request) && $user && ((int) $user->tenant_id !== 1 || !$user->hasRole('Super-admin'))) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            abort(403, 'Akses khusus platform admin.');
        }

        if (!$user) {
            return $next($request);
        }

        $expectedUrl = $this->expectedUrl($request, $user);
        if ($expectedUrl) {
            return redirect()->away($expectedUrl);
        }

        return $next($request);
    }

    private function expectedUrl(Request $request, Authenticatable $user): ?string
    {
        $expectedHost = null;
        $saasDomain = (string) config('multitenancy.saas_domain');
        $platformSubdomain = (string) config('multitenancy.platform_admin_subdomain', 'dash');

        if ((int) $user->tenant_id === 1 && method_exists($user, 'hasRole') && $user->hasRole('Super-admin')) {
            $expectedHost = $platformSubdomain . '.' . $saasDomain;
        } elseif (!empty($user->tenant?->slug)) {
            $expectedHost = $user->tenant->slug . '.' . $saasDomain;
        }

        if (!$expectedHost || $request->getHost() === $expectedHost) {
            return null;
        }

        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: ($request->isSecure() ? 'https' : 'http');

        return $scheme . '://' . $expectedHost . $request->getRequestUri();
    }

    private function isPlatformAdminHost(Request $request): bool
    {
        $host = $request->getHost();
        $saasDomain = (string) config('multitenancy.saas_domain');
        $platformSubdomain = (string) config('multitenancy.platform_admin_subdomain', 'dash');

        return $host === $platformSubdomain . '.' . $saasDomain;
    }
}
