<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
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
            $request->session()->put('tenant_slug', $tenant?->slug);
        }

        try {
            return $next($request);
        } finally {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
            TenantContext::forget();
        }
    }
}
