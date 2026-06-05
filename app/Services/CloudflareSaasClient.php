<?php

namespace App\Services;

use App\Models\CloudflareSaasSetting;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use RuntimeException;

class CloudflareSaasClient
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function createCustomHostname(string $hostname, array $payload = []): array
    {
        return $this->request('post', '/custom_hostnames', array_merge([
            'hostname' => $hostname,
            'ssl' => [
                'method' => 'txt',
                'type' => 'dv',
            ],
        ], $payload));
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomHostname(string $cloudflareHostnameId): array
    {
        return $this->request('get', '/custom_hostnames/' . $cloudflareHostnameId);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteCustomHostname(string $cloudflareHostnameId): array
    {
        return $this->request('delete', '/custom_hostnames/' . $cloudflareHostnameId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFallbackOrigin(): array
    {
        return $this->request('get', '/custom_hostnames/fallback_origin');
    }

    /**
     * @return array<string, mixed>
     */
    public function updateFallbackOrigin(string $hostname): array
    {
        return $this->request('put', '/custom_hostnames/fallback_origin', [
            'origin' => $hostname,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        $settings = CloudflareSaasSetting::current();
        $zoneId = trim((string) $settings->zone_id);
        $token = trim((string) $settings->api_token);

        if ($zoneId === '' || $token === '') {
            throw new RuntimeException('Cloudflare SaaS belum dikonfigurasi oleh owner.');
        }

        /** @var Response $response */
        $response = $this->http
            ->baseUrl("https://api.cloudflare.com/client/v4/zones/{$zoneId}")
            ->acceptJson()
            ->withToken($token)
            ->send(strtoupper($method), $path, [
                'json' => $payload,
            ]);

        $data = $response->json();

        if (!$response->successful() || !data_get($data, 'success', false)) {
            $message = trim((string) collect(data_get($data, 'errors', []))
                ->map(fn (array $error) => (string) ($error['message'] ?? 'Cloudflare request failed'))
                ->implode('; '));

            throw new RuntimeException($message !== '' ? $message : 'Cloudflare request failed.');
        }

        return (array) data_get($data, 'result', []);
    }
}
