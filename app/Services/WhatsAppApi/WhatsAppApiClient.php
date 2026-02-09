<?php

namespace App\Services\WhatsAppApi;

use App\Models\WhatsAppApiSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatsAppApiClient
{
    public function testConnection(WhatsAppApiSetting $setting): array
    {
        return match ($setting->provider) {
            'meta_cloud' => $this->testMetaCloud($setting),
            'third_party' => $this->testThirdParty($setting),
            default => [
                'ok' => false,
                'message' => 'Provider belum didukung.',
            ],
        };
    }

    private function testMetaCloud(WhatsAppApiSetting $setting): array
    {
        if (blank($setting->phone_number_id)) {
            return [
                'ok' => false,
                'message' => 'Phone Number ID wajib diisi untuk Meta Cloud API.',
            ];
        }

        if (blank($setting->access_token)) {
            return [
                'ok' => false,
                'message' => 'Access token belum diisi.',
            ];
        }

        $url = sprintf(
            'https://graph.facebook.com/v19.0/%s?fields=display_phone_number,verified_name',
            $setting->phone_number_id
        );

        return $this->performRequest($setting, $url, 'Meta Cloud API');
    }

    private function testThirdParty(WhatsAppApiSetting $setting): array
    {
        if (blank($setting->base_url)) {
            return [
                'ok' => false,
                'message' => 'Base URL wajib diisi untuk provider third party.',
            ];
        }

        if (blank($setting->access_token)) {
            return [
                'ok' => false,
                'message' => 'Access token belum diisi.',
            ];
        }

        $baseUrl = rtrim($setting->base_url, '/');
        $url = $baseUrl . '/health';

        return $this->performRequest($setting, $url, 'Third Party API');
    }

    private function performRequest(WhatsAppApiSetting $setting, string $url, string $label): array
    {
        $timeout = $setting->timeout_seconds ?: 30;

        try {
            $response = Http::timeout($timeout)
                ->withToken($setting->access_token)
                ->acceptJson()
                ->get($url);
        } catch (ConnectionException $exception) {
            $message = str_contains(strtolower($exception->getMessage()), 'timed out')
                ? 'Connection timeout.'
                : 'Tidak dapat terhubung ke server provider.';

            return [
                'ok' => false,
                'message' => $message,
            ];
        }

        if (in_array($response->status(), [401, 403], true)) {
            return [
                'ok' => false,
                'message' => 'Unauthorized, token invalid.',
            ];
        }

        if (!$response->successful()) {
            $snippet = Str::limit(trim((string) $response->body()), 200, '...');
            $details = $snippet !== '' ? " Response: {$snippet}" : '';

            return [
                'ok' => false,
                'message' => "Gagal terhubung. Status {$response->status()}.{$details}",
            ];
        }

        $data = $response->json();
        $extra = '';
        if (is_array($data)) {
            $name = data_get($data, 'verified_name');
            $number = data_get($data, 'display_phone_number');
            if ($name || $number) {
                $parts = array_filter([$name, $number]);
                $extra = ' (' . implode(' - ', $parts) . ')';
            }
        }

        return [
            'ok' => true,
            'message' => "Berhasil terhubung ke {$label}.{$extra}",
        ];
    }
}
