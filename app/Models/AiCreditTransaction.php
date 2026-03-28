<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCreditTransaction extends Model
{
    protected $fillable = [
        'tenant_id',
        'kind',
        'credits',
        'source',
        'reference',
        'notes',
        'expires_at',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'credits' => 'integer',
        'expires_at' => 'datetime',
        'meta' => 'array',
        'created_by' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
