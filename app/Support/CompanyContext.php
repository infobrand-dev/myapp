<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CompanyContext
{
    private static ?int $currentCompanyId = null;

    public static function currentId(): ?int
    {
        return self::$currentCompanyId;
    }

    public static function currentCompany(): ?Company
    {
        if (!Schema::hasTable('companies') || self::$currentCompanyId === null) {
            return null;
        }

        return Company::query()
            ->whereKey(self::$currentCompanyId)
            ->where('tenant_id', TenantContext::currentId())
            ->first();
    }

    public static function setCurrentId(?int $companyId): void
    {
        self::$currentCompanyId = $companyId ?: null;
    }

    public static function forget(): void
    {
        self::$currentCompanyId = null;
    }

    public static function resolveIdFromRequest(Request $request): ?int
    {
        if (!Schema::hasTable('companies')) {
            return null;
        }

        $tenantId = TenantContext::currentId();
        $candidates = array_filter([
            self::normalizeInteger($request->attributes->get('company_id')),
            self::normalizeInteger($request->header('X-Company-Id')),
            self::normalizeInteger($request->query('company_id')),
            self::normalizeInteger($request->session()?->get('company_id')),
        ]);

        foreach ($candidates as $candidate) {
            if (self::companyExists($candidate, $tenantId)) {
                return $candidate;
            }
        }

        $slugCandidates = array_filter([
            self::normalizeSlug($request->attributes->get('company')),
            self::normalizeSlug($request->header('X-Company-Slug')),
            self::normalizeSlug($request->query('company')),
            self::normalizeSlug($request->session()?->get('company_slug')),
        ]);

        foreach ($slugCandidates as $slug) {
            $companyId = Company::query()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->where('is_active', true)
                ->value('id');

            if ($companyId) {
                return (int) $companyId;
            }
        }

        return (int) (Company::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id') ?: 0) ?: null;
    }

    private static function companyExists(int $companyId, int $tenantId): bool
    {
        return Company::query()
            ->whereKey($companyId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->exists();
    }

    private static function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $companyId = (int) $value;

        return $companyId > 0 ? $companyId : null;
    }

    private static function normalizeSlug(mixed $value): ?string
    {
        $slug = trim((string) $value);

        return $slug !== '' ? $slug : null;
    }
}
