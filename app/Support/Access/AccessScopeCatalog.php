<?php

namespace App\Support\Access;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;

class AccessScopeCatalog
{
    public function current(): AccessScope
    {
        return new AccessScope(
            TenantContext::currentId(),
            CompanyContext::currentId(),
            BranchContext::currentId(),
            (array) config('platform-core.entitlement.future_scopes', [])
        );
    }
}
