<?php

namespace App\Models\Concerns;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;

trait ResolvesRouteBindingWithinTenant
{
    protected bool $routeBindingRequiresCompanyScope = false;
    protected bool $routeBindingAllowsNullCompany = false;
    protected bool $routeBindingRequiresBranchScope = false;
    protected bool $routeBindingAllowsNullBranch = true;

    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId());

        if ($this->routeBindingRequiresCompanyScope) {
            $companyId = CompanyContext::currentId();
            $query->where(function ($builder) use ($companyId) {
                $builder->where('company_id', $companyId);

                if ($this->routeBindingAllowsNullCompany) {
                    $builder->orWhereNull('company_id');
                }
            });
        }

        if ($this->routeBindingRequiresBranchScope) {
            if (BranchContext::currentId() === null) {
                if ($this->routeBindingAllowsNullBranch) {
                    $query->whereNull('branch_id');
                }
            } else {
                $query->where(function ($builder) {
                    $builder->where('branch_id', BranchContext::currentId());

                    if ($this->routeBindingAllowsNullBranch) {
                        $builder->orWhereNull('branch_id');
                    }
                });
            }
        }

        return $query->firstOrFail();
    }
}
