<?php

namespace App\Models;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriodLock extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'locked_from',
        'locked_until',
        'status',
        'notes',
        'created_by',
        'released_by',
        'released_at',
    ];

    protected $casts = [
        'locked_from' => 'date',
        'locked_until' => 'date',
        'released_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return BranchContext::applyScope(
            $this->where($field ?? $this->getRouteKeyName(), $value)
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
        )->firstOrFail();
    }
}
