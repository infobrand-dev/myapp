<?php

namespace App\Jobs;

use App\Models\TenantDomain;
use App\Services\TenantCustomDomainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AuditTenantDomainDnsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(TenantCustomDomainService $service): void
    {
        TenantDomain::query()
            ->whereIn('status', [
                TenantDomain::STATUS_PENDING_DNS,
                TenantDomain::STATUS_PENDING_OWNERSHIP,
                TenantDomain::STATUS_PENDING_SSL,
                TenantDomain::STATUS_ACTIVE,
            ])
            ->get()
            ->each(fn (TenantDomain $domain) => $service->syncDomain($domain));
    }
}
