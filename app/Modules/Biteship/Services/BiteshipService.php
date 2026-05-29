<?php

namespace App\Modules\Biteship\Services;

use App\Modules\Biteship\Models\BiteshipSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiteshipService
{
    public function getSettings(): ?BiteshipSetting
    {
        return BiteshipSetting::forCurrentTenant();
    }

    public function isConfigured(): bool
    {
        $setting = $this->getSettings();

        return $setting && $setting->is_active && $setting->api_key;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function quoteRates(array $payload): array
    {
        $setting = $this->getSettings();

        if (!$setting || !$setting->is_active || !$setting->api_key) {
            throw new \RuntimeException('Biteship belum dikonfigurasi atau tidak aktif.');
        }

        if (empty($payload['origin_postal_code']) || empty($payload['destination_postal_code'])) {
            throw new \RuntimeException('Biteship membutuhkan origin dan destination postal code.');
        }

        $couriers = trim((string) ($payload['couriers'] ?? ''));
        if ($couriers === '') {
            $couriers = implode(',', (array) ($setting->default_couriers ?? []));
        }

        if ($couriers === '') {
            throw new \RuntimeException('Masukkan daftar courier atau set default couriers di pengaturan Biteship.');
        }

        $requestPayload = [
            'origin_postal_code' => (int) $payload['origin_postal_code'],
            'destination_postal_code' => (int) $payload['destination_postal_code'],
            'couriers' => $couriers,
            'items' => [[
                'name' => (string) $payload['item_name'],
                'description' => $payload['item_description'] ?? null,
                'value' => (int) round((float) $payload['item_value']),
                'weight' => (int) $payload['item_weight'],
                'quantity' => (int) $payload['item_quantity'],
                'length' => $payload['item_length'] ?: null,
                'width' => $payload['item_width'] ?: null,
                'height' => $payload['item_height'] ?: null,
            ]],
        ];

        $response = Http::withHeaders([
            'authorization' => (string) $setting->api_key,
            'content-type' => 'application/json',
        ])->timeout(30)->post($setting->getApiBaseUrl() . '/v1/rates/couriers', $requestPayload);

        if (!$response->successful()) {
            $message = $response->json('error') ?? $response->json('message') ?? $response->body();
            Log::error('Biteship quote failed', [
                'status' => $response->status(),
                'error' => $message,
                'payload' => $requestPayload,
            ]);
            throw new \RuntimeException('Gagal mengambil rate Biteship: ' . $message);
        }

        $raw = (array) $response->json();
        $pricing = collect((array) data_get($raw, 'pricing', data_get($raw, 'data.pricing', [])));

        if ($pricing->isEmpty()) {
            $pricing = collect((array) data_get($raw, 'data', []))
                ->filter(fn ($item) => is_array($item) && array_key_exists('price', $item));
        }

        return [
            'provider' => 'biteship',
            'options' => $pricing
                ->map(fn ($item) => [
                    'courier_code' => (string) data_get($item, 'courier_code', data_get($item, 'company', '')),
                    'courier_name' => (string) data_get($item, 'courier_name', data_get($item, 'company', '')),
                    'service_name' => (string) data_get($item, 'courier_service_name', data_get($item, 'type', '')),
                    'service_code' => (string) data_get($item, 'courier_service_code', data_get($item, 'type', '')),
                    'price' => (float) data_get($item, 'price', 0),
                    'currency' => (string) data_get($item, 'currency', 'IDR'),
                    'etd' => (string) data_get($item, 'duration', data_get($item, 'shipment_duration_range')),
                    'raw' => (array) $item,
                ])
                ->values()
                ->all(),
            'raw' => $raw,
        ];
    }
}
