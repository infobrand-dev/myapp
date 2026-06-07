<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformAuditLog extends Model
{
    protected $table = 'platform_audit_logs';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'actor_type',
        'actor_id',
        'impersonator_type',
        'impersonator_id',
        'entity_type',
        'entity_id',
        'action',
        'changed_fields',
        'before',
        'after',
        'context',
        'occurred_at',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'before' => 'array',
        'after' => 'array',
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];
}
