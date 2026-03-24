<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromSubdomain
{
    /**
     * Detect the tenant from the subdomain when running in SaaS mode.
     *
     * This middleware runs before ResolveTenantContext and sets
     * `request->attributes->tenant_id`, which is the highest-priority
     * source in TenantContext::resolveIdFromRequest() — meaning it cannot
     * be overridden by session, header, or query-string manipulation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('multitenancy.mode') !== 'saas') {
            return $next($request);
        }

        $saasDomain = config('multitenancy.saas_domain');
        $host = $request->getHost(); // strips port

        // Only act when the request host is a direct subdomain of SAAS_DOMAIN
        if (!str_ends_with($host, '.' . $saasDomain)) {
            return $next($request);
        }

        $slug = substr($host, 0, -strlen('.' . $saasDomain));

        // Refuse reserved slugs early — prevents confusion with system routes
        if (in_array($slug, config('multitenancy.reserved_slugs', []), true)) {
            return $next($request);
        }

        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($tenant === null) {
            abort(404, "Tenant «{$slug}» tidak ditemukan atau tidak aktif.");
        }

        // Inject into request attributes — highest priority, server-side only
        $request->attributes->set('tenant_id', $tenant->id);
        $request->attributes->set('tenant_slug', $slug);

        return $next($request);
    }
}
