<?php

namespace App\Models;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriodClosing extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'closing_scope_key',
        'period_start',
        'period_end',
        'status',
        'revenue_total',
        'expense_total',
        'net_income',
        'retained_earnings_account_code',
        'retained_earnings_account_name',
        'closing_journal_id',
        'period_lock_id',
        'closed_by',
        'closed_at',
        'notes',
        'meta',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'revenue_total' => 'decimal:2',
        'expense_total' => 'decimal:2',
        'net_income' => 'decimal:2',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function closingJournal(): BelongsTo
    {
        return $this->belongsTo(AccountingJournal::class, 'closing_journal_id');
    }

    public function periodLock(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriodLock::class, 'period_lock_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
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
