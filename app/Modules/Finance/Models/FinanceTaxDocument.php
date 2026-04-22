<?php

namespace App\Modules\Finance\Models;

use App\Models\Branch;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinanceTaxDocument extends Model
{
    public const TYPE_OUTPUT_VAT = 'output_vat';
    public const TYPE_INPUT_VAT = 'input_vat';
    public const TYPE_WITHHOLDING = 'withholding';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_REPLACED = 'replaced';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'source_document_type',
        'source_document_id',
        'contact_id',
        'finance_tax_rate_id',
        'document_type',
        'document_status',
        'document_number',
        'external_document_number',
        'transaction_date',
        'document_date',
        'tax_period_month',
        'tax_period_year',
        'counterparty_name_snapshot',
        'counterparty_tax_id_snapshot',
        'counterparty_tax_name_snapshot',
        'counterparty_tax_address_snapshot',
        'taxable_base',
        'tax_amount',
        'withheld_amount',
        'currency_code',
        'reference_note',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'document_date' => 'date',
        'taxable_base' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'withheld_amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function sourceDocument(): MorphTo
    {
        return $this->morphTo();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(FinanceTaxRate::class, 'finance_tax_rate_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
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
        return BranchContext::applyScope(
            $this->where($field ?: $this->getRouteKeyName(), $value)
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
        )->firstOrFail();
    }

    public static function documentTypeOptions(): array
    {
        return [
            self::TYPE_OUTPUT_VAT => 'PPN Keluaran',
            self::TYPE_INPUT_VAT => 'PPN Masukan',
            self::TYPE_WITHHOLDING => 'PPh / Withholding',
        ];
    }

    public static function documentStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_REPLACED => 'Replaced',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
