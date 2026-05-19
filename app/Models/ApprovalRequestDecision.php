<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequestDecision extends Model
{
    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';

    protected $fillable = [
        'approval_request_id',
        'tenant_id',
        'company_id',
        'branch_id',
        'approver_id',
        'decision',
        'notes',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
