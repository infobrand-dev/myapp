<?php

namespace App\Http\Middleware;

use App\Support\SaasHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApexHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('multitenancy.mode') !== 'saas') {
            return $next($request);
        }

        if (SaasHost::isApexHost($request)) {
            return $next($request);
        }

        if (!$request->attributes->get('tenant_id') && !$request->attributes->get('platform_admin_host')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(404);
        }

        return redirect()->to($request->getSchemeAndHttpHost() . '/');
    }
}
