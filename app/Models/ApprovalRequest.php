<?php

namespace App\Models;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPLIED = 'applied';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'module',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'status',
        'required_approvals',
        'current_approvals',
        'payload_hash',
        'payload',
        'reason',
        'decision_notes',
        'requested_by',
        'approved_by',
        'decided_at',
        'consumed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'required_approvals' => 'integer',
        'current_approvals' => 'integer',
        'decided_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ApprovalRequestDecision::class, 'approval_request_id')->latest('decided_at');
    }

    public function isFullyApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && (int) $this->current_approvals >= max(1, (int) $this->required_approvals);
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
