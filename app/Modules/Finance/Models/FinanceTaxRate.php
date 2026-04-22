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

    public const SCOPE_GENERAL = 'general';
    public const SCOPE_PPN_OUTPUT = 'ppn_output';
    public const SCOPE_PPN_INPUT = 'ppn_input';
    public const SCOPE_PPH_21 = 'pph_21';
    public const SCOPE_PPH_23 = 'pph_23';
    public const SCOPE_PPH_22 = 'pph_22';
    public const SCOPE_PPH_FINAL = 'pph_final';

    protected $table = 'finance_tax_rates';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'code',
        'name',
        'tax_type',
        'tax_scope',
        'jurisdiction_code',
        'legal_basis',
        'document_label',
        'requires_tax_number',
        'requires_counterparty_tax_id',
        'rate_percent',
        'is_inclusive',
        'is_active',
        'sales_account_code',
        'purchase_account_code',
        'withholding_account_code',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rate_percent' => 'decimal:4',
        'requires_tax_number' => 'boolean',
        'requires_counterparty_tax_id' => 'boolean',
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

    public static function taxScopeOptions(): array
    {
        return [
            self::SCOPE_GENERAL => 'General',
            self::SCOPE_PPN_OUTPUT => 'PPN Keluaran',
            self::SCOPE_PPN_INPUT => 'PPN Masukan',
            self::SCOPE_PPH_21 => 'PPh 21',
            self::SCOPE_PPH_23 => 'PPh 23',
            self::SCOPE_PPH_22 => 'PPh 22',
            self::SCOPE_PPH_FINAL => 'PPh Final',
        ];
    }
}
