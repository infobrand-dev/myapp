<?php

namespace App\Modules\RajaOngkir\Services;

use App\Modules\RajaOngkir\Models\RajaOngkirSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RajaOngkirService
{
    public function getSettings(): ?RajaOngkirSetting
    {
        return RajaOngkirSetting::forCurrentTenant();
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
            throw new \RuntimeException('RajaOngkir belum dikonfigurasi atau tidak aktif.');
        }

        $origin = trim((string) ($payload['origin_area_id'] ?? $setting->default_origin_area_id ?? ''));
        $destination = trim((string) ($payload['destination_area_id'] ?? ''));
        $couriers = trim((string) ($payload['couriers'] ?? ''));

        if ($origin === '' || $destination === '') {
            throw new \RuntimeException('RajaOngkir membutuhkan origin area id dan destination area id.');
        }

        if ($couriers === '') {
            $couriers = implode(':', (array) ($setting->default_couriers ?? []));
        }

        if ($couriers === '') {
            throw new \RuntimeException('Masukkan courier atau set default couriers di pengaturan RajaOngkir.');
        }

        $requestPayload = [
            'origin' => $origin,
            'destination' => $destination,
            'weight' => (int) $payload['item_weight'],
            'courier' => str_replace(',', ':', $couriers),
            'price' => 'lowest',
        ];

        $response = Http::asForm()
            ->withHeaders([
                'key' => (string) $setting->api_key,
            ])->timeout(30)
            ->post($setting->getApiBaseUrl() . '/calculate/domestic-cost', $requestPayload);

        if (!$response->successful()) {
            $message = data_get($response->json(), 'meta.message') ?? $response->json('message') ?? $response->body();
            Log::error('RajaOngkir quote failed', [
                'status' => $response->status(),
                'error' => $message,
                'payload' => $requestPayload,
            ]);
            throw new \RuntimeException('Gagal mengambil rate RajaOngkir: ' . $message);
        }

        $raw = (array) $response->json();
        $rows = collect((array) data_get($raw, 'data', []))
            ->filter(fn ($item) => is_array($item));

        return [
            'provider' => 'rajaongkir',
            'options' => $rows
                ->map(fn ($item) => [
                    'courier_code' => (string) data_get($item, 'code', data_get($item, 'courier', '')),
                    'courier_name' => (string) data_get($item, 'name', data_get($item, 'courier', '')),
                    'service_name' => (string) data_get($item, 'service', data_get($item, 'service_name', '')),
                    'service_code' => (string) data_get($item, 'service', ''),
                    'price' => (float) data_get($item, 'cost', data_get($item, 'price', 0)),
                    'currency' => 'IDR',
                    'etd' => (string) data_get($item, 'etd', data_get($item, 'description')),
                    'raw' => (array) $item,
                ])
                ->values()
                ->all(),
            'raw' => $raw,
        ];
    }
}
