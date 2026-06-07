<?php

namespace App\Modules\Crm\Support;

use Illuminate\Support\Facades\Auth;
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

        $query = $query
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

        $user = Auth::user();

        if ($user && !$user->hasAnyRole(['Super-admin', 'Admin']) && !$user->can('crm.view_all')) {
            $query->where(function ($visibility) use ($user): void {
                $visibility->where('owner_user_id', $user->id)
                    ->orWhereNull('owner_user_id');
            });
        }

        return $query;
    }
}
