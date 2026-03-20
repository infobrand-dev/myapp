<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = TenantContext::resolveIdFromRequest($request);

        TenantContext::setCurrentId($tenantId);
        $request->attributes->set('tenant_id', $tenantId);

        if ($request->hasSession()) {
            $request->session()->put('tenant_id', $tenantId);
        }

        try {
            return $next($request);
        } finally {
            TenantContext::forget();
        }
    }
}
