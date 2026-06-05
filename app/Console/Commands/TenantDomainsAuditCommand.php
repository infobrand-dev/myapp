<?php

namespace App\Console\Commands;

use App\Models\CloudflareSaasSetting;
use App\Models\TenantDomain;
use Illuminate\Console\Command;

class TenantDomainsAuditCommand extends Command
{
    protected $signature = 'tenant:domains-audit';

    protected $description = 'Audit tenant custom domains and Cloudflare control-plane health.';

    public function handle(): int
    {
        $settings = CloudflareSaasSetting::current();

        $this->line('Cloudflare control plane: ' . ($settings->is_active ? 'active' : 'inactive'));

        if ($settings->last_error_summary) {
            $this->warn('Cloudflare last error: ' . $settings->last_error_summary);
        }

        $rows = TenantDomain::query()
            ->selectRaw('status, count(*) as aggregate_count')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No tenant custom domains found.');

            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            $this->line(sprintf('%s: %d', $row->status, $row->aggregate_count));
        }

        $problematic = TenantDomain::query()
            ->whereIn('status', [
                TenantDomain::STATUS_BLOCKED,
                TenantDomain::STATUS_FAILED,
            ])
            ->orWhere('last_error_code', 'provider_unreachable')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        if ($problematic->isNotEmpty()) {
            $this->warn('Problematic domains:');

            foreach ($problematic as $domain) {
                $this->line(sprintf(
                    '- tenant=%d host=%s status=%s error=%s',
                    $domain->tenant_id,
                    $domain->normalizedHostname(),
                    $domain->status,
                    $domain->last_error_code ?: '-'
                ));
            }
        }

        return self::SUCCESS;
    }
}
