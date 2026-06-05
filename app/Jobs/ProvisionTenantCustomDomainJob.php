<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\TenantCustomDomainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProvisionTenantCustomDomainJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $hostname,
        public readonly ?int $actorUserId = null,
    ) {
    }

    public function handle(TenantCustomDomainService $service): void
    {
        $tenant = Tenant::query()->findOrFail($this->tenantId);

        $service->requestDomain($tenant, $this->hostname, $this->actorUserId);
    }
}
