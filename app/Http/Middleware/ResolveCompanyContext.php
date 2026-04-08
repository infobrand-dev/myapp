<?php

namespace App\Http\Middleware;

use App\Support\CompanyContext;
use App\Support\TenantContext;
use App\Support\WorkspaceContextProvisioner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        if ($request->user()) {
            app(WorkspaceContextProvisioner::class)->ensureForTenant(TenantContext::currentId(), $request->user());
        }

        $companyId = CompanyContext::resolveIdFromRequest($request);

        CompanyContext::setCurrentId($companyId);
        $request->attributes->set('company_id', $companyId);

        if ($request->hasSession()) {
            $request->session()->put('company_id', $companyId);
            $request->session()->put('company_slug', CompanyContext::currentCompany()?->slug);
        }

        try {
            return $next($request);
        } finally {
            CompanyContext::forget();
        }
    }
}
