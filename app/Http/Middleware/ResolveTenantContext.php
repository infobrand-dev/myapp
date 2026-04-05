<?php

namespace App\Http\Middleware;

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

        if ($this->shouldBypassTenantContext($request)) {
            return $next($request);
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

        if ($this->isApexAllowedRoute($request)) {
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
                $target = sprintf(
                    '%s://%s.%s/login',
                    $scheme,
                    $tenant->slug,
                    config('multitenancy.saas_domain')
                );

                return redirect()->away($target);
            }
        }

        return redirect()
            ->route('onboarding.create')
            ->with('warning', 'Masuk melalui subdomain workspace Anda. Contoh: tenantanda.' . config('multitenancy.saas_domain'));
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

    private function isApexAllowedRoute(Request $request): bool
    {
        return $request->path() === '/'
            || $request->is('meetra')
            || $request->is('omnichannel')
            || $request->is('accounting')
            || $request->is('mulai-digital')
            || $request->is('website-aplikasi-bisnis')
            || $request->is('jasa-pembuatan-website')
            || $request->is('tentang-kami')
            || $request->is('affiliate-program')
            || $request->is('onboarding')
            || $request->is('aff/*')
            || $request->is('workspace')
            || $request->is('health')
            || $request->is('locale/switch')
            || $request->is('keamanan-data')
            || $request->is('kebijakan-privasi')
            || $request->is('syarat-ketentuan');
    }

    private function shouldBypassTenantContext(Request $request): bool
    {
        if ($this->isPlatformWebhookRoute($request) && !$request->user()) {
            return true;
        }

        return config('multitenancy.mode') === 'saas'
            && $this->isApexAllowedRoute($request)
            && !$request->attributes->get('tenant_id')
            && !$request->user();
    }

    private function isPlatformWebhookRoute(Request $request): bool
    {
        return $request->is('social-media/webhook')
            || $request->is('social-media/webhook/x')
            || $request->is('whatsapp-api/webhook')
            || $request->is('platform/billing/midtrans/webhook');
    }
}
