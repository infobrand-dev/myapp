<?php

namespace App\Modules\Crm\Support;

use App\Models\Tenant;
use Illuminate\Support\Str;

class CrmIntegrationService
{
    /**
     * @return array<string, mixed>
     */
    public function settings(Tenant $tenant): array
    {
        $meta = (array) ($tenant->meta ?? []);
        $crm = (array) ($meta['crm_integrations'] ?? []);

        if (empty($crm['lead_capture_token'])) {
            $crm['lead_capture_token'] = $this->generateToken();
            $meta['crm_integrations'] = $crm;
            $tenant->forceFill(['meta' => $meta])->save();
        }

        return $crm;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Tenant $tenant, array $data): array
    {
        $meta = (array) ($tenant->meta ?? []);
        $crm = (array) ($meta['crm_integrations'] ?? []);

        $crm['lead_capture_token'] = !empty($data['rotate_lead_capture_token'])
            ? $this->generateToken()
            : ($crm['lead_capture_token'] ?? $this->generateToken());

        $crm['on_won'] = [
            'enabled' => (bool) ($data['on_won_enabled'] ?? false),
            'create_sales_quotation' => (bool) ($data['create_sales_quotation'] ?? false),
            'create_draft_sale' => (bool) ($data['create_draft_sale'] ?? false),
            'finalize_draft_sale' => (bool) ($data['finalize_draft_sale'] ?? false),
            'default_product_id' => !empty($data['default_product_id']) ? (int) $data['default_product_id'] : null,
        ];

        $crm['owner_routing_rules'] = collect(preg_split('/\r\n|\r|\n/', (string) ($data['owner_routing_rules_text'] ?? '')))
            ->map(function (string $line): ?array {
                $line = trim($line);
                if ($line === '' || !str_contains($line, '|')) {
                    return null;
                }

                [$keyword, $ownerId] = array_map('trim', explode('|', $line, 2));
                if ($keyword === '' || !is_numeric($ownerId)) {
                    return null;
                }

                $field = null;
                $cleanKeyword = $keyword;
                if (str_contains($keyword, ':')) {
                    [$candidateField, $candidateKeyword] = array_map('trim', explode(':', $keyword, 2));
                    if (in_array(strtolower($candidateField), ['source', 'provider', 'campaign', 'adset', 'form', 'title'], true) && $candidateKeyword !== '') {
                        $field = strtolower($candidateField);
                        $cleanKeyword = $candidateKeyword;
                    }
                }

                return [
                    'field' => $field,
                    'keyword' => $cleanKeyword,
                    'owner_user_id' => (int) $ownerId,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $meta['crm_integrations'] = $crm;
        $tenant->forceFill(['meta' => $meta])->save();

        return $crm;
    }

    /**
     * @return array<string, mixed>
     */
    public function current(?Tenant $tenant = null): array
    {
        $tenant ??= \App\Support\TenantContext::currentTenant();

        return $tenant ? $this->settings($tenant) : [];
    }

    public function tokenMatches(Tenant $tenant, ?string $providedToken): bool
    {
        $settings = $this->settings($tenant);
        $expected = trim((string) ($settings['lead_capture_token'] ?? ''));
        $provided = trim((string) $providedToken);

        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }

    private function generateToken(): string
    {
        return 'crm_' . Str::random(40);
    }
}
