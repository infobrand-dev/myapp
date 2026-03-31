<?php

namespace App\Modules\WhatsAppApi\Models;

use App\Support\TenantContext;
use App\Support\NormalizesPgsqlBooleanAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppWebhookEvent extends Model
{
    use HasFactory;
    use NormalizesPgsqlBooleanAttributes;

    protected $table = 'whatsapp_webhook_events';

    protected $fillable = [
        'tenant_id',
        'instance_id',
        'provider',
        'event_key',
        'headers',
        'payload',
        'signature_valid',
        'process_status',
        'retry_count',
        'error_message',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'headers' => 'array',
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'instance_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function canReprocess(): bool
    {
        if ($this->process_status === 'processed') {
            return false;
        }

        if ($this->provider === 'gateway') {
            return true;
        }

        return $this->provider === 'cloud' && $this->signature_valid === true;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }

    public function sanitizedHeaders(): array
    {
        return $this->sanitizePayload((array) ($this->headers ?? []));
    }

    public function sanitizedPayload(): array
    {
        return $this->sanitizePayload((array) ($this->payload ?? []));
    }

    private function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = [
            'authorization',
            'cookie',
            'token',
            'api_token',
            'access_token',
            'cloud_token',
            'verify_token',
            'wa_cloud_verify_token',
            'wa_cloud_app_secret',
            'app_secret',
            'x-bridge-token',
            'x-hub-signature-256',
            'signature',
            'signature_key',
        ];

        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
                continue;
            }

            if (in_array(mb_strtolower((string) $key), $sensitiveKeys, true)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
