<?php

namespace App\Modules\Crm\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MetaLeadAdsPayloadMapper
{
    /**
     * @return array<string, mixed>
     */
    public function map(Request $request): array
    {
        $payload = $request->all();
        $entry = Arr::first((array) ($payload['entry'] ?? []));
        $change = Arr::first((array) ($entry['changes'] ?? []));
        $value = (array) ($change['value'] ?? []);
        $fieldData = collect((array) ($value['field_data'] ?? []))
            ->filter(fn ($item) => is_array($item) && !empty($item['name']))
            ->mapWithKeys(function (array $item): array {
                $name = Str::snake((string) $item['name']);
                $values = (array) ($item['values'] ?? []);

                return [$name => trim((string) ($values[0] ?? ''))];
            })
            ->all();

        $name = $fieldData['full_name']
            ?? $fieldData['nama_lengkap']
            ?? $fieldData['name']
            ?? null;

        $email = $fieldData['email']
            ?? $fieldData['email_address']
            ?? null;

        $phone = $fieldData['phone_number']
            ?? $fieldData['phone']
            ?? $fieldData['nomor_hp']
            ?? null;

        $campaign = trim((string) ($value['campaign_name'] ?? $request->input('campaign_name', '')));
        $adset = trim((string) ($value['adset_name'] ?? $request->input('adset_name', '')));
        $form = trim((string) ($value['form_name'] ?? $request->input('form_name', '')));
        $platform = trim((string) ($value['platform'] ?? 'meta_ads'));

        return [
            'name' => $name,
            'email' => $email,
            'mobile' => $phone,
            'title' => $this->buildTitle($name, $campaign, $form),
            'lead_source' => 'meta_ads',
            'provider' => $platform !== '' ? $platform : 'meta_ads',
            'external_reference' => (string) ($value['leadgen_id'] ?? $request->input('external_reference', '')),
            'campaign_name' => $campaign !== '' ? $campaign : null,
            'adset_name' => $adset !== '' ? $adset : null,
            'form_name' => $form !== '' ? $form : null,
            'notes' => $this->buildNotes($fieldData, $value),
            'meta_payload' => $payload,
        ];
    }

    /**
     * @param  array<string, string>  $fieldData
     * @param  array<string, mixed>  $value
     */
    private function buildNotes(array $fieldData, array $value): ?string
    {
        $lines = [];

        foreach ($fieldData as $key => $val) {
            if ($val === '' || in_array($key, ['full_name', 'nama_lengkap', 'name', 'email', 'email_address', 'phone_number', 'phone', 'nomor_hp'], true)) {
                continue;
            }

            $lines[] = Str::headline($key) . ': ' . $val;
        }

        if (!empty($value['created_time'])) {
            $lines[] = 'Leadgen created at: ' . $value['created_time'];
        }

        return $lines !== [] ? implode("\n", $lines) : null;
    }

    private function buildTitle(?string $name, string $campaign, string $form): string
    {
        $subject = $name ?: 'Meta Lead';
        $context = $campaign !== '' ? $campaign : ($form !== '' ? $form : 'Meta Ads');

        return 'Lead ' . $subject . ' - ' . $context;
    }
}
