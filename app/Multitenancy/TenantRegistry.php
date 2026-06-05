<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use App\Models\TenantDomain;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class TenantRegistry
{
    public function findById(int $tenantId): ?Tenant
    {
        return Tenant::query()
            ->with($this->supportedRelations())
            ->find($tenantId);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::query()
            ->with($this->supportedRelations())
            ->where('slug', $slug)
            ->first();
    }

    public function findByDomain(string $host): ?Tenant
    {
        if (!Schema::connection(config('multitenancy.central_connection', 'central'))->hasTable('tenant_domains')) {
            return null;
        }

        $normalized = $this->normalizeDomain($host);

        $tenantId = TenantDomain::query()
            ->hostname($normalized)
            ->where('status', TenantDomain::STATUS_ACTIVE)
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

    /**
     * @return array<int, string>
     */
    private function supportedRelations(): array
    {
        $connection = config('multitenancy.central_connection', 'central');
        $relations = ['domains'];

        if (Schema::connection($connection)->hasTable('tenant_topologies')) {
            $relations[] = 'topology.database.server';
        }

        return $relations;
    }
}
