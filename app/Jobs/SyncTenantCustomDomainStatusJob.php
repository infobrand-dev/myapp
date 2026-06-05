<?php

namespace App\Jobs;

use App\Models\TenantDomain;
use App\Services\TenantCustomDomainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTenantCustomDomainStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $tenantDomainId,
        public readonly ?int $actorUserId = null,
    ) {
    }

    public function handle(TenantCustomDomainService $service): void
    {
        $domain = TenantDomain::query()->findOrFail($this->tenantDomainId);

        $service->syncDomain($domain, $this->actorUserId);
    }
}
