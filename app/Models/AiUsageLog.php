<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'source_module',
        'source_type',
        'source_id',
        'chatbot_account_id',
        'provider',
        'model',
        'billing_mode',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'credits_used',
        'estimated_cost',
        'metadata',
        'used_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'source_id' => 'integer',
        'chatbot_account_id' => 'integer',
        'billing_mode' => 'string',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'credits_used' => 'integer',
        'estimated_cost' => 'decimal:6',
        'metadata' => 'array',
        'used_at' => 'datetime',
    ];
}
