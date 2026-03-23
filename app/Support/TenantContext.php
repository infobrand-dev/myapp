<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TenantContext
{
    private static ?int $currentTenantId = null;

    public static function currentId(): int
    {
        if (self::$currentTenantId === null) {
            // Warn when the context was never set — most likely a queued job, artisan
            // command, or scheduled task that forgot to call setCurrentId().
            // All DB queries will fall back to tenant 1 which may cause cross-tenant leaks.
            if (app()->runningInConsole()) {
                logger()->warning('TenantContext::currentId() called without a resolved context (console/job). Defaulting to tenant 1. Call TenantContext::setCurrentId() before executing tenant-scoped queries.');
            }

            return 1;
        }

        return self::$currentTenantId;
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

        $session = $request->hasSession() ? $request->session() : null;

        $candidates = array_filter([
            self::normalizeInteger($request->attributes->get('tenant_id')),
            self::normalizeInteger($request->header('X-Tenant-Id')),
            self::normalizeInteger($request->query('tenant_id')),
            self::normalizeInteger($session ? $session->get('tenant_id') : null),
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
            self::normalizeSlug($session ? $session->get('tenant_slug') : null),
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

    public static function resolveIdFromUser(?User $user): ?int
    {
        if (!$user || !Schema::hasTable('tenants')) {
            return null;
        }

        $tenantId = self::normalizeInteger($user->tenant_id ?? null);

        if ($tenantId === null) {
            return null;
        }

        return self::tenantExists($tenantId) ? $tenantId : null;
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
