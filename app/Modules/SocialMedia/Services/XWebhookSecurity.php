<?php

namespace App\Modules\SocialMedia\Services;

class XWebhookSecurity
{
    public function isConfigured(): bool
    {
        return filled(config('services.x_api.client_secret'));
    }

    /**
     * @return array{response_token:string}
     */
    public function buildCrcResponse(string $crcToken): array
    {
        $secret = $this->consumerSecret();

        return [
            'response_token' => 'sha256=' . base64_encode(hash_hmac('sha256', $crcToken, $secret, true)),
        ];
    }

    public function verifySignature(string $rawBody, string $signatureHeader): bool
    {
        $secret = $this->consumerSecret();
        $expected = 'sha256=' . base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($expected, trim($signatureHeader));
    }

    private function consumerSecret(): string
    {
        return trim((string) config('services.x_api.client_secret', ''));
    }
}
