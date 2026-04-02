<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantByoAiRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'requested_by',
        'status',
        'preferred_provider',
        'intended_volume',
        'chatbot_account_count',
        'channel_count',
        'technical_contact_name',
        'technical_contact_email',
        'notes',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'meta',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'requested_by' => 'integer',
        'chatbot_account_count' => 'integer',
        'channel_count' => 'integer',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
