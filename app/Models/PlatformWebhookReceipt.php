<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformWebhookReceipt extends Model
{
    protected $table = 'platform_webhook_receipts';

    protected $fillable = [
        'tenant_id',
        'provider',
        'endpoint',
        'signature_valid',
        'dedupe_key',
        'status',
        'headers',
        'payload',
        'processed_at',
        'failed_at',
        'error_message',
        'meta',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'headers' => 'array',
        'payload' => 'array',
        'meta' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
