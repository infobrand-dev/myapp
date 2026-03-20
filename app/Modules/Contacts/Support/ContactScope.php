<?php

namespace App\Modules\Contacts\Support;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ContactScope
{
    public const LEVEL_TENANT = 'tenant';
    public const LEVEL_COMPANY = 'company';
    public const LEVEL_BRANCH = 'branch';

    public static function applyVisibilityScope(Builder $query, ?int $tenantId = null, ?int $companyId = null, ?int $branchId = null): Builder
    {
        $tenantId ??= TenantContext::currentId();
        $companyId ??= CompanyContext::currentId();
        $branchId = func_num_args() >= 4 ? $branchId : BranchContext::currentId();

        return $query
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $builder) use ($companyId, $branchId): void {
                $builder->where(function (Builder $tenantWide): void {
                    $tenantWide->whereNull('company_id')
                        ->whereNull('branch_id');
                });

                if ($companyId !== null) {
                    $builder->orWhere(function (Builder $companyWide) use ($companyId): void {
                        $companyWide->where('company_id', $companyId)
                            ->whereNull('branch_id');
                    });
                }

                if ($companyId !== null && $branchId !== null) {
                    $builder->orWhere(function (Builder $branchScoped) use ($companyId, $branchId): void {
                        $branchScoped->where('company_id', $companyId)
                            ->where('branch_id', $branchId);
                    });
                }
            });
    }

    public static function applyWriteScope(array $data, ?string $level = null): array
    {
        $level ??= self::LEVEL_COMPANY;

        return match ($level) {
            self::LEVEL_TENANT => array_merge($data, [
                'company_id' => null,
                'branch_id' => null,
            ]),
            self::LEVEL_COMPANY => array_merge($data, [
                'company_id' => CompanyContext::currentId(),
                'branch_id' => null,
            ]),
            self::LEVEL_BRANCH => array_merge($data, [
                'company_id' => self::requireCompanyId(),
                'branch_id' => self::requireBranchId(),
            ]),
            default => throw ValidationException::withMessages([
                'scope' => 'Scope contact tidak dikenali.',
            ]),
        };
    }

    public static function visibleLevels(): array
    {
        $levels = [
            self::LEVEL_TENANT,
            self::LEVEL_COMPANY,
        ];

        if (BranchContext::currentId() !== null) {
            $levels[] = self::LEVEL_BRANCH;
        }

        return $levels;
    }

    public static function detectLevel(object $contact): string
    {
        if (!empty($contact->branch_id)) {
            return self::LEVEL_BRANCH;
        }

        if (!empty($contact->company_id)) {
            return self::LEVEL_COMPANY;
        }

        return self::LEVEL_TENANT;
    }

    private static function requireCompanyId(): int
    {
        $companyId = CompanyContext::currentId();
        if ($companyId === null) {
            throw ValidationException::withMessages([
                'scope' => 'Company aktif wajib tersedia untuk contact company/branch scoped.',
            ]);
        }

        return (int) $companyId;
    }

    private static function requireBranchId(): int
    {
        $branchId = BranchContext::currentId();
        if ($branchId === null) {
            throw ValidationException::withMessages([
                'scope' => 'Pilih branch aktif sebelum membuat contact branch-scoped.',
            ]);
        }

        return (int) $branchId;
    }
}
