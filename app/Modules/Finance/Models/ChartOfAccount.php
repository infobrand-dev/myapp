<?php

namespace App\Modules\Finance\Models;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ChartOfAccount extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_EXPENSE = 'expense';

    public const NORMAL_DEBIT = 'debit';
    public const NORMAL_CREDIT = 'credit';

    public const SECTION_BALANCE_SHEET = 'balance_sheet';
    public const SECTION_PROFIT_LOSS = 'profit_loss';

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'parent_id',
        'code',
        'name',
        'account_type',
        'normal_balance',
        'report_section',
        'is_postable',
        'is_active',
        'sort_order',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_postable' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        $column = $query->qualifyColumn('is_active');

        if (DB::connection($this->getConnectionName())->getDriverName() === 'pgsql') {
            return $query->whereRaw($column . ' is true');
        }

        return $query->where('is_active', true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->orderBy('sort_order')
            ->orderBy('code');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->firstOrFail();
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_ASSET => 'Asset',
            self::TYPE_LIABILITY => 'Liability',
            self::TYPE_EQUITY => 'Equity',
            self::TYPE_REVENUE => 'Revenue',
            self::TYPE_EXPENSE => 'Expense',
        ];
    }

    public static function normalBalanceOptions(): array
    {
        return [
            self::NORMAL_DEBIT => 'Debit',
            self::NORMAL_CREDIT => 'Credit',
        ];
    }

    public static function reportSectionOptions(): array
    {
        return [
            self::SECTION_BALANCE_SHEET => 'Balance Sheet',
            self::SECTION_PROFIT_LOSS => 'Profit & Loss',
        ];
    }
}
