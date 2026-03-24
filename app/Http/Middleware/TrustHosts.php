<?php

namespace App\Http\Middleware;

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

        return $hosts;
    }
}
