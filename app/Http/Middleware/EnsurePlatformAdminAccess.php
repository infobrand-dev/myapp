<?php

namespace App\Http\Middleware;

use App\Services\DomainHandoffService;
use App\Services\TenantHostResolver;
use App\Support\SaasHost;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdminAccess
{
    public function __construct(
        private readonly DomainHandoffService $handoff,
        private readonly TenantHostResolver $hostResolver,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (config('multitenancy.mode') !== 'saas') {
            return $next($request);
        }

        $user = $request->user();

        if ($this->isPlatformAdminHost($request) && $user && ((int) $user->tenant_id !== 1 || !$this->isPlatformSuperAdmin($user))) {
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

        if ((int) $user->tenant_id === 1 && $this->isPlatformSuperAdmin($user)) {
            $expectedHost = SaasHost::platformHost($request);
        } elseif (!empty($user->tenant?->slug)) {
            $expectedHost = $this->hostResolver->canonicalHostForTenant($request, $user->tenant);
        }

        if (!$expectedHost || $request->getHost() === $expectedHost) {
            return null;
        }

        if ((int) $user->tenant_id !== 1 && $user->tenant) {
            return $this->handoff->issue($user, $expectedHost, $this->handoff->intendedPath($request));
        }

        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: ($request->isSecure() ? 'https' : 'http');

        return $scheme . '://' . $expectedHost . $request->getRequestUri();
    }

    private function isPlatformAdminHost(Request $request): bool
    {
        return SaasHost::isPlatformAdminHost($request);
    }

    private function isPlatformSuperAdmin(Authenticatable $user): bool
    {
        if (!method_exists($user, 'hasRole') || (int) data_get($user, 'tenant_id', 0) !== 1) {
            return false;
        }

        if ($user->hasRole('Super-admin')) {
            return true;
        }

        if (!method_exists($user, 'roles')) {
            return false;
        }

        return $user->roles()
            ->where('name', 'Super-admin')
            ->where(function ($query): void {
                $query->whereNull('roles.tenant_id')
                    ->orWhere('roles.tenant_id', 1);
            })
            ->exists();
    }
}
