<?php

namespace App\Http\Middleware;

use App\Models\TenantDomain;
use App\Multitenancy\TenantRegistry;
use App\Services\TenantHostResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromSubdomain
{
    /** @var TenantRegistry */
    private $registry;

    /** @var TenantHostResolver */
    private $hostResolver;

    public function __construct(TenantRegistry $registry, TenantHostResolver $hostResolver)
    {
        $this->registry = $registry;
        $this->hostResolver = $hostResolver;
    }

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
        URL::defaults([]);

        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        if (config('multitenancy.mode') !== 'saas') {
            return $next($request);
        }

        $host = $request->getHost(); // strips port
        $customDomain = $this->hostResolver->findTenantDomainByHost($host);

        if ($customDomain !== null) {
            $tenant = $customDomain->tenant;

            if ($tenant === null) {
                abort(404, 'Tenant domain tidak valid.');
            }

            if (! $tenant->is_active || $tenant->status !== 'active') {
                return response()->view('errors.tenant-suspended', [], 403);
            }

            $request->attributes->set('tenant_id', $tenant->id);
            $request->attributes->set('tenant_slug', $tenant->slug);
            $request->attributes->set('tenant_domain_id', $customDomain->id);
            $request->attributes->set('tenant_custom_host', $customDomain->normalizedHostname());

            return $next($request);
        }

        $rootDomain = $this->resolveMatchingRootDomain($host);

        // Only act when the request host is a direct subdomain of a known SaaS root domain.
        if ($rootDomain === null) {
            return $next($request);
        }

        $slug = substr($host, 0, -strlen('.' . $rootDomain));
        $platformSubdomain = (string) config('multitenancy.platform_admin_subdomain', 'dash');

        if ($slug === '' || str_contains($slug, '.')) {
            return $next($request);
        }

        if ($slug === $platformSubdomain) {
            $request->attributes->set('tenant_id', 1);
            $request->attributes->set('tenant_slug', $platformSubdomain);
            $request->attributes->set('platform_admin_host', true);
            URL::defaults(['account' => $platformSubdomain]);

            return $next($request);
        }

        // Refuse reserved slugs early — prevents confusion with system routes
        if (in_array($slug, config('multitenancy.reserved_slugs', []), true)) {
            return $next($request);
        }

        $tenant = $this->registry->findByDomain($host) ?: $this->registry->findBySlug($slug);

        if ($tenant === null) {
            abort(404, "Workspace «{$slug}» tidak ditemukan.");
        }

        if (! $tenant->is_active || $tenant->status !== 'active') {
            return response()->view('errors.tenant-suspended', [], 403);
        }

        // Inject into request attributes — highest priority, server-side only
        $request->attributes->set('tenant_id', $tenant->id);
        $request->attributes->set('tenant_slug', $slug);
        $request->attributes->set('tenant_domain_id', optional(TenantDomain::query()->where('tenant_id', $tenant->id)->canonical()->active()->first())->id);
        URL::defaults(['account' => $slug]);

        return $next($request);
    }

    private function resolveMatchingRootDomain(string $host): ?string
    {
        foreach ($this->candidateRootDomains() as $rootDomain) {
            if ($host !== $rootDomain && str_ends_with($host, '.' . $rootDomain)) {
                return $rootDomain;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function candidateRootDomains(): array
    {
        $domains = [
            $this->normalizeDomain(config('multitenancy.saas_domain')),
            $this->normalizeDomain(parse_url((string) config('app.url'), PHP_URL_HOST)),
        ];

        return array_values(array_unique(array_filter($domains)));
    }

    private function normalizeDomain(mixed $value): ?string
    {
        $domain = trim(strtolower((string) $value));

        return $domain !== '' ? $domain : null;
    }
}
