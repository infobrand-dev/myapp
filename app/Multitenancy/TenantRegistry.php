<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use App\Models\TenantDomain;
use Illuminate\Support\Str;

class TenantRegistry
{
    public function findById(int $tenantId): ?Tenant
    {
        return Tenant::query()
            ->with(['topology.database.server', 'domains'])
            ->find($tenantId);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::query()
            ->with(['topology.database.server', 'domains'])
            ->where('slug', $slug)
            ->first();
    }

    public function findByDomain(string $host): ?Tenant
    {
        $normalized = $this->normalizeDomain($host);

        $tenantId = TenantDomain::query()
            ->where('domain', $normalized)
            ->where('status', 'active')
            ->value('tenant_id');

        return $tenantId ? $this->findById((int) $tenantId) : null;
    }

    public function generateSafeSchemaName(string $slug, ?string $prefix = null): string
    {
        $base = Str::slug($slug, '_');
        $base = preg_replace('/[^a-z0-9_]/', '_', strtolower($base ?? 'tenant'));
        $base = trim((string) $base, '_');
        $base = $base === '' ? 'tenant' : $base;
        $base = Str::limit($base, 40, '');

        $prefix = trim((string) $prefix);
        if ($prefix !== '') {
            $prefix = preg_replace('/[^a-z0-9_]/', '_', strtolower($prefix));
            $prefix = rtrim((string) $prefix, '_') . '_';
        }

        return ($prefix ?: 'tenant_') . $base;
    }

    private function normalizeDomain(string $domain): string
    {
        return Str::lower(trim($domain));
    }
}
