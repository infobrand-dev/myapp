<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformEventOutbox extends Model
{
    protected $table = 'platform_event_outbox';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'event_name',
        'event_version',
        'idempotency_key',
        'subject_type',
        'subject_id',
        'payload',
        'occurred_at',
        'status',
        'dispatched_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
