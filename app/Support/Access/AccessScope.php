<?php

namespace App\Support\Access;

class AccessScope
{
    /**
     * @param  array<int, string>  $futureScopes
     */
    public function __construct(
        public readonly ?int $tenantId,
        public readonly ?int $companyId,
        public readonly ?int $branchId,
        public readonly array $futureScopes = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'future_scopes' => $this->futureScopes,
        ];
    }
}
