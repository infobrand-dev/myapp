<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDomainEvent extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_domain_id',
        'tenant_id',
        'event',
        'actor_user_id',
        'actor_scope',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function tenantDomain(): BelongsTo
    {
        return $this->belongsTo(TenantDomain::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
