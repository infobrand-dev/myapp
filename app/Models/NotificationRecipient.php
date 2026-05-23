<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRecipient extends Model
{
    protected $fillable = [
        'notification_id',
        'tenant_id',
        'company_id',
        'branch_id',
        'user_id',
        'is_read',
        'read_at',
        'dismissed_at',
        'archived_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'company_id' => 'integer',
        'branch_id' => 'integer',
        'user_id' => 'integer',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(CoreNotification::class, 'notification_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
