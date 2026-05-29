<?php

namespace App\Http\Middleware;

use App\Support\SaasHost;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        if ($response = $this->redirectGuestProtectedApexRoute($request)) {
            return $response;
        }

        if ($response = $this->redirectGuestAuthToTenantSubdomain($request)) {
            return $response;
        }

        $tenantId = TenantContext::resolveIdFromRequest($request);
        $userTenantId = TenantContext::resolveIdFromUser($request->user());

        if ($request->user() && $userTenantId === null) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->forget(['tenant_id', 'tenant_slug']);
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            TenantContext::forget();
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);

            abort(403, 'Tenant akun tidak valid atau tidak aktif.');
        }

        if ($userTenantId !== null) {
            $tenantId = $userTenantId;
        }

        TenantContext::setCurrentId($tenantId);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        $request->attributes->set('tenant_id', $tenantId);

        if ($request->hasSession()) {
            $request->session()->put('tenant_id', $tenantId);
            $tenant = TenantContext::currentTenant();
            $request->session()->put('tenant_slug', optional($tenant)->slug);
        }

        try {
            return $next($request);
        } finally {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
            TenantContext::forget();
        }
    }

    private function redirectGuestAuthToTenantSubdomain(Request $request): ?Response
    {
        if (config('multitenancy.mode') !== 'saas') {
            return null;
        }

        if ($request->user()) {
            return null;
        }

        if (!$this->isGuestAuthRoute($request)) {
            return null;
        }

        if ($request->attributes->get('tenant_id')) {
            return null;
        }

        $workspace = trim((string) $request->input('workspace', ''));
        if ($workspace !== '') {
            $tenant = Tenant::query()
                ->where('slug', $workspace)
                ->active()
                ->first();

            if ($tenant) {
                $scheme = $request->isSecure() ? 'https' : 'http';
                $target = sprintf('%s://%s/login', $scheme, SaasHost::tenantHost($request, $tenant->slug));

                return redirect()->away($target);
            }
        }

        return redirect()
            ->route('onboarding.create')
            ->with('warning', 'Masuk melalui subdomain workspace Anda. Contoh: tenantanda.' . (SaasHost::candidateRootDomains()[0] ?? config('multitenancy.saas_domain')));
    }

    private function isGuestAuthRoute(Request $request): bool
    {
        return $request->is('login')
            || $request->is('register')
            || $request->is('forgot-password')
            || $request->is('reset-password/*')
            || $request->is('reset-password')
            || $request->is('two-factor-challenge');
    }

    private function redirectGuestProtectedApexRoute(Request $request): ?Response
    {
        if (config('multitenancy.mode') !== 'saas') {
            return null;
        }

        if ($request->user() || $request->attributes->get('tenant_id')) {
            return null;
        }

        if (!$this->isProtectedShellRoute($request)) {
            return null;
        }

        return redirect()->guest(route('login'));
    }

    private function isProtectedShellRoute(Request $request): bool
    {
        return $request->is('dashboard')
            || $request->is('platform')
            || $request->is('platform/*')
            || $request->is('profile')
            || $request->is('settings')
            || $request->is('settings/*')
            || $request->is('users')
            || $request->is('users/*')
            || $request->is('roles')
            || $request->is('roles/*')
            || $request->is('modules')
            || $request->is('modules/*')
            || $request->is('presence')
            || $request->is('presence/*');
    }

}
