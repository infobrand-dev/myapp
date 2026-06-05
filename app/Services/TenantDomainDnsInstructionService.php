<?php

namespace App\Services;

use App\Models\CloudflareSaasSetting;
use App\Models\TenantDomain;

class TenantDomainDnsInstructionService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function instructionsFor(TenantDomain $domain): array
    {
        $instructions = [];

        if ($domain->ownership_dns_name && $domain->ownership_dns_value) {
            $instructions[] = [
                'kind' => 'ownership',
                'type' => 'TXT',
                'name' => $domain->ownership_dns_name,
                'value' => $domain->ownership_dns_value,
            ];
        }

        $settings = CloudflareSaasSetting::current();

        if ($domain->hostname_type === 'apex') {
            foreach ((array) $settings->apex_ipv4_targets as $target) {
                $instructions[] = [
                    'kind' => 'routing',
                    'type' => 'A',
                    'name' => $domain->normalizedHostname(),
                    'value' => $target,
                ];
            }

            foreach ((array) $settings->apex_ipv6_targets as $target) {
                $instructions[] = [
                    'kind' => 'routing',
                    'type' => 'AAAA',
                    'name' => $domain->normalizedHostname(),
                    'value' => $target,
                ];
            }

            return $instructions;
        }

        $instructions[] = [
            'kind' => 'routing',
            'type' => $domain->routing_record_type ?: 'CNAME',
            'name' => $domain->routing_record_name ?: $domain->normalizedHostname(),
            'value' => $domain->routing_record_value ?: $settings->cname_target,
        ];

        return $instructions;
    }
}
