<?php

namespace App\Modules\Finance\Models;

use App\Models\Branch;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'finance_account_id',
        'period_start',
        'period_end',
        'statement_ending_balance',
        'book_closing_balance',
        'difference_amount',
        'status',
        'notes',
        'meta',
        'created_by',
        'updated_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'statement_ending_balance' => 'decimal:2',
        'book_closing_balance' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class)->latest('id');
    }

    public function statementImports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class)->latest('id');
    }

    public function statementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class)->latest('transaction_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return BranchContext::applyScope(
            $this->where($field ?: $this->getRouteKeyName(), $value)
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
        )->firstOrFail();
    }
}
