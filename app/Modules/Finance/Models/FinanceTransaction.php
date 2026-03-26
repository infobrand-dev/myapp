<?php

namespace App\Modules\Finance\Models;

use App\Models\Company;
use App\Models\User;
use App\Models\Branch;
use App\Modules\PointOfSale\Models\PosCashSession;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTransaction extends Model
{
    public const TYPE_CASH_IN = 'cash_in';
    public const TYPE_CASH_OUT = 'cash_out';
    public const TYPE_EXPENSE = 'expense';

    protected $table = 'finance_transactions';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'transaction_number',
        'transaction_type',
        'transaction_date',
        'amount',
        'finance_category_id',
        'notes',
        'branch_id',
        'pos_cash_session_id',
        'created_by',
        'updated_by',
        'meta',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'finance_category_id')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(PosCashSession::class, 'pos_cash_session_id')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId());
    }

    public function isCashOut(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_CASH_OUT, self::TYPE_EXPENSE], true);
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
