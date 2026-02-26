<?php

namespace App\Modules\WhatsAppApi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_webhook_events';

    protected $fillable = [
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
        'headers' => 'array',
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'instance_id');
    }
}

