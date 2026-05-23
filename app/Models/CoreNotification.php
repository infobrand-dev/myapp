<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoreNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'module',
        'type',
        'severity',
        'status',
        'title',
        'body',
        'resource_type',
        'resource_id',
        'dedupe_key',
        'actions',
        'meta',
        'occurred_at',
        'first_seen_at',
        'last_seen_at',
        'occurrence_count',
        'resolved_at',
        'dismissed_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'company_id' => 'integer',
        'branch_id' => 'integer',
        'resource_id' => 'integer',
        'actions' => 'array',
        'meta' => 'array',
        'occurred_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'notification_id');
    }
}
