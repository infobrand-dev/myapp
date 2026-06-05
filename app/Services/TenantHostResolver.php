<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TenantHostResolver
{
    public function canonicalHostForTenant(Request $request, Tenant $tenant): string
    {
        $custom = $this->canonicalCustomHostname($tenant);

        if ($custom !== null) {
            return $custom;
        }

        return \App\Support\SaasHost::defaultTenantSubdomainHost($request, (string) $tenant->slug);
    }

    public function canonicalCustomHostname(Tenant $tenant): ?string
    {
        $cacheKey = 'tenant:canonical-domain:' . $tenant->id;

        return Cache::remember($cacheKey, 60, function () use ($tenant): ?string {
            if (!Schema::connection(config('multitenancy.central_connection', 'central'))->hasTable('tenant_domains')) {
                return null;
            }

            return TenantDomain::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', TenantDomain::STATUS_ACTIVE)
                ->where('is_canonical', true)
                ->orderByDesc('is_primary')
                ->orderByDesc('id')
                ->get()
                ->map(fn (TenantDomain $domain) => $domain->normalizedHostname())
                ->first();
        });
    }

    public function findTenantDomainByHost(string $host): ?TenantDomain
    {
        $normalized = strtolower(trim($host));
        $cacheKey = 'tenant-domain:host:' . $normalized;

        return Cache::remember($cacheKey, 60, function () use ($normalized): ?TenantDomain {
            if (!Schema::connection(config('multitenancy.central_connection', 'central'))->hasTable('tenant_domains')) {
                return null;
            }

            return TenantDomain::query()
                ->with('tenant')
                ->hostname($normalized)
                ->where('status', TenantDomain::STATUS_ACTIVE)
                ->first();
        });
    }

    public function clearTenantCache(int $tenantId, string $hostname): void
    {
        Cache::forget('tenant:canonical-domain:' . $tenantId);
        Cache::forget('tenant-domain:host:' . strtolower(trim($hostname)));
    }
}
