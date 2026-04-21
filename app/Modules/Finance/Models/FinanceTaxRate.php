<?php

namespace App\Modules\Finance\Models;

use App\Support\CompanyContext;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FinanceTaxRate extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    public const TYPE_SALES = 'sales';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_WITHHOLDING = 'withholding';
    public const TYPE_OTHER = 'other';

    protected $table = 'finance_tax_rates';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'code',
        'name',
        'tax_type',
        'rate_percent',
        'is_inclusive',
        'is_active',
        'sales_account_code',
        'purchase_account_code',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rate_percent' => 'decimal:4',
        'is_inclusive' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->firstOrFail();
    }

    public static function taxTypeOptions(): array
    {
        return [
            self::TYPE_SALES => 'Sales Tax / PPN Keluaran',
            self::TYPE_PURCHASE => 'Purchase Tax / PPN Masukan',
            self::TYPE_WITHHOLDING => 'Withholding / PPh',
            self::TYPE_OTHER => 'Other',
        ];
    }
}
