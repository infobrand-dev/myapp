<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isPlatformAdminHost($request)) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if ((int) $user->tenant_id !== 1 || !$user->hasRole('Super-admin')) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            abort(403, 'Akses khusus platform admin.');
        }

        return $next($request);
    }

    private function isPlatformAdminHost(Request $request): bool
    {
        if (config('multitenancy.mode') !== 'saas') {
            return false;
        }

        $host = $request->getHost();
        $saasDomain = (string) config('multitenancy.saas_domain');
        $platformSubdomain = (string) config('multitenancy.platform_admin_subdomain', 'dash');

        return $host === $platformSubdomain . '.' . $saasDomain;
    }
}
