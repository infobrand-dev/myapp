<?php

namespace App\Models;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class ApprovalMatrixRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'module',
        'action',
        'min_amount',
        'required_approvals',
        'maker_checker_required',
        'max_backdate_days',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'required_approvals' => 'integer',
        'maker_checker_required' => 'boolean',
        'max_backdate_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function resolveRouteBinding($value, $field = null)
    {
        return BranchContext::applyScope(
            $this->where($field ?? $this->getRouteKeyName(), $value)
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
        )->firstOrFail();
    }
}
