<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformActivityEvent extends Model
{
    protected $table = 'platform_activity_events';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'source_module',
        'event_type',
        'subject_type',
        'subject_id',
        'actor_type',
        'actor_id',
        'summary',
        'payload',
        'actions',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'actions' => 'array',
        'occurred_at' => 'datetime',
    ];
}
