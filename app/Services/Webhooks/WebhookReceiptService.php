<?php

namespace App\Services\Webhooks;

use App\Models\PlatformWebhookReceipt;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class WebhookReceiptService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function receive(
        string $provider,
        string $endpoint,
        Request $request,
        array $payload,
        ?string $dedupeKey = null
    ): PlatformWebhookReceipt {
        $dedupeKey = $dedupeKey ?: sha1($provider . '|' . $endpoint . '|' . $request->getContent());

        $existing = PlatformWebhookReceipt::query()
            ->where('provider', $provider)
            ->where('endpoint', $endpoint)
            ->where('dedupe_key', $dedupeKey)
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->forceFill([
                'status' => 'duplicate',
                'meta' => array_merge((array) $existing->meta, ['duplicate_seen_at' => now()->toIso8601String()]),
            ])->save();

            return $existing;
        }

        return PlatformWebhookReceipt::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'provider' => $provider,
            'endpoint' => $endpoint,
            'signature_valid' => null,
            'dedupe_key' => $dedupeKey,
            'status' => 'received',
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'payload' => $payload,
            'meta' => [
                'ip' => $request->ip(),
                'received_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function markSignature(PlatformWebhookReceipt $receipt, bool $valid): void
    {
        $receipt->forceFill([
            'signature_valid' => $valid,
            'status' => $valid ? $receipt->status : 'invalid_signature',
        ])->save();
    }

    public function markProcessed(PlatformWebhookReceipt $receipt, array $meta = []): void
    {
        $receipt->forceFill([
            'status' => 'processed',
            'processed_at' => now(),
            'failed_at' => null,
            'error_message' => null,
            'meta' => array_merge((array) $receipt->meta, $meta),
        ])->save();
    }

    public function markFailed(PlatformWebhookReceipt $receipt, string $message, array $meta = []): void
    {
        $receipt->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $message,
            'meta' => array_merge((array) $receipt->meta, $meta),
        ])->save();
    }

    public function markReplayed(PlatformWebhookReceipt $receipt): void
    {
        $receipt->forceFill([
            'status' => 'replayed',
            'meta' => array_merge((array) $receipt->meta, ['replayed_at' => now()->toIso8601String()]),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $key => $value) {
            $lower = strtolower((string) $key);
            if (str_contains($lower, 'authorization') || str_contains($lower, 'cookie')) {
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
