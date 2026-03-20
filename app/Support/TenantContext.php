<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TenantContext
{
    private static ?int $currentTenantId = null;

    public static function currentId(): int
    {
        return self::$currentTenantId ?? 1;
    }

    public static function currentTenant(): ?Tenant
    {
        if (!Schema::hasTable('tenants')) {
            return null;
        }

        return Tenant::query()
            ->whereKey(self::currentId())
            ->first();
    }

    public static function setCurrentId(?int $tenantId): void
    {
        self::$currentTenantId = $tenantId ?: 1;
    }

    public static function forget(): void
    {
        self::$currentTenantId = null;
    }

    public static function resolveIdFromRequest(Request $request): int
    {
        if (!Schema::hasTable('tenants')) {
            return 1;
        }

        $candidates = array_filter([
            self::normalizeInteger($request->attributes->get('tenant_id')),
            self::normalizeInteger($request->header('X-Tenant-Id')),
            self::normalizeInteger($request->query('tenant_id')),
            self::normalizeInteger($request->session()?->get('tenant_id')),
            self::normalizeInteger(optional($request->user())->tenant_id ?? null),
        ]);

        foreach ($candidates as $candidate) {
            if (self::tenantExists($candidate)) {
                return $candidate;
            }
        }

        $slugCandidates = array_filter([
            self::normalizeSlug($request->attributes->get('tenant')),
            self::normalizeSlug($request->header('X-Tenant-Slug')),
            self::normalizeSlug($request->query('tenant')),
            self::normalizeSlug($request->session()?->get('tenant_slug')),
        ]);

        foreach ($slugCandidates as $slug) {
            $tenantId = Tenant::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->value('id');

            if ($tenantId) {
                return (int) $tenantId;
            }
        }

        return self::tenantExists(1) ? 1 : (int) (Tenant::query()->where('is_active', true)->value('id') ?: 1);
    }

    private static function tenantExists(int $tenantId): bool
    {
        return Tenant::query()
            ->whereKey($tenantId)
            ->where('is_active', true)
            ->exists();
    }

    private static function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $tenantId = (int) $value;

        return $tenantId > 0 ? $tenantId : null;
    }

    private static function normalizeSlug(mixed $value): ?string
    {
        $slug = trim((string) $value);

        return $slug !== '' ? $slug : null;
    }
}
