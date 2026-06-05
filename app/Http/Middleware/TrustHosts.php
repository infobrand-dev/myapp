<?php

namespace App\Http\Middleware;

use App\Models\TenantDomain;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts()
    {
        $hosts = [
            $this->allSubdomainsOfApplicationUrl(),
        ];

        // In SaaS mode also trust all subdomains of the dedicated SaaS domain
        if (config('multitenancy.mode') === 'saas') {
            $saasDomain = config('multitenancy.saas_domain');
            $hosts[] = '(.+\.)?' . preg_quote($saasDomain);
        }

        return array_values(array_filter(array_merge($hosts, $this->customTenantHosts())));
    }

    /**
     * @return array<int, string>
     */
    private function customTenantHosts(): array
    {
        return Cache::remember('trust-hosts:tenant-custom-domains', 60, static function (): array {
            if (config('multitenancy.mode') !== 'saas') {
                return [];
            }

            if (!Schema::connection(config('multitenancy.central_connection', 'central'))->hasTable('tenant_domains')) {
                return [];
            }

            return TenantDomain::query()
                ->where('status', TenantDomain::STATUS_ACTIVE)
                ->get()
                ->map(fn (TenantDomain $domain) => '^' . preg_quote($domain->normalizedHostname(), '/') . '$')
                ->values()
                ->all();
        });
    }
}
