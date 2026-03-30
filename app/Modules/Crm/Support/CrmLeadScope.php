<?php

namespace App\Modules\Crm\Support;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;

class CrmLeadScope
{
    public static function applyVisibilityScope($query, ?int $tenantId = null, ?int $companyId = null, ?int $branchId = null)
    {
        $tenantId ??= TenantContext::currentId();
        $companyId ??= CompanyContext::currentId();
        $branchId = func_num_args() >= 4 ? $branchId : BranchContext::currentId();

        return $query
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($companyId, $branchId): void {
                $builder->where(function ($tenantWide): void {
                    $tenantWide->whereNull('company_id')
                        ->whereNull('branch_id');
                });

                if ($companyId !== null) {
                    $builder->orWhere(function ($companyWide) use ($companyId): void {
                        $companyWide->where('company_id', $companyId)
                            ->whereNull('branch_id');
                    });
                }

                if ($companyId !== null && $branchId !== null) {
                    $builder->orWhere(function ($branchScoped) use ($companyId, $branchId): void {
                        $branchScoped->where('company_id', $companyId)
                            ->where('branch_id', $branchId);
                    });
                }
            });
    }
}
