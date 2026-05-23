<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentNumberingRule extends Model
{
    public const RESET_NEVER = 'never';
    public const RESET_MONTHLY = 'monthly';
    public const RESET_YEARLY = 'yearly';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'scope_key',
        'document_type',
        'prefix',
        'number_format',
        'padding',
        'next_number',
        'last_period',
        'reset_period',
        'notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'company_id' => 'integer',
        'branch_id' => 'integer',
        'padding' => 'integer',
        'next_number' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function definitions(): array
    {
        return [
            'sale' => [
                'label' => 'Sales Invoice',
                'default_prefix' => 'SAL',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Draft sale, finalize sale, dan POS receipt memakai nomor sale ini.',
            ],
            'sale_quotation' => [
                'label' => 'Quotation',
                'default_prefix' => 'QUO',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Dokumen quotation penjualan.',
            ],
            'sale_order' => [
                'label' => 'Sales Order',
                'default_prefix' => 'SO',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Dokumen sales order sebelum invoicing.',
            ],
            'sale_return' => [
                'label' => 'Sales Return',
                'default_prefix' => 'RET',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Dokumen retur penjualan.',
            ],
            'credit_memo' => [
                'label' => 'Credit Memo',
                'default_prefix' => 'CRM',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Penyesuaian pengurang piutang customer dari sale.',
            ],
            'purchase' => [
                'label' => 'Purchase Bill',
                'default_prefix' => 'PUR',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Draft purchase dan finalize purchase supplier.',
            ],
            'purchase_request' => [
                'label' => 'Purchase Request',
                'default_prefix' => 'PR',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Permintaan pembelian internal sebelum purchase order.',
            ],
            'purchase_order' => [
                'label' => 'Purchase Order',
                'default_prefix' => 'PO',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Dokumen purchase order ke supplier.',
            ],
            'purchase_receipt' => [
                'label' => 'Goods Receipt',
                'default_prefix' => 'GRN',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Penerimaan barang dari purchase.',
            ],
            'debit_note' => [
                'label' => 'Debit Note',
                'default_prefix' => 'DNT',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Penyesuaian pengurang hutang supplier dari purchase.',
            ],
            'write_off_note' => [
                'label' => 'Write-off Note',
                'default_prefix' => 'WOF',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Dokumen write-off hutang pembelian.',
            ],
            'payment' => [
                'label' => 'Payment',
                'default_prefix' => 'PAY',
                'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
                'applies_to' => 'Pembayaran masuk dan keluar di module payments.',
            ],
            'tax_output_vat' => [
                'label' => 'Tax Output VAT',
                'default_prefix' => 'FPK',
                'default_format' => '{PREFIX}-{YYYYMM}-{SEQ}',
                'applies_to' => 'Register PPN keluaran / faktur pajak keluaran.',
            ],
            'tax_input_vat' => [
                'label' => 'Tax Input VAT',
                'default_prefix' => 'FPM',
                'default_format' => '{PREFIX}-{YYYYMM}-{SEQ}',
                'applies_to' => 'Register PPN masukan / invoice pajak supplier.',
            ],
            'tax_withholding' => [
                'label' => 'Tax Withholding',
                'default_prefix' => 'BUPOT',
                'default_format' => '{PREFIX}-{YYYYMM}-{SEQ}',
                'applies_to' => 'Register PPh / bukti potong.',
            ],
        ];
    }

    public static function definition(string $documentType): array
    {
        return static::definitions()[$documentType] ?? [
            'label' => $documentType,
            'default_prefix' => strtoupper($documentType),
            'default_format' => '{PREFIX}-{YYYYMMDD}-{SEQ}',
            'applies_to' => 'Dokumen '.$documentType,
        ];
    }

    public static function supportedDocumentTypes(): array
    {
        return array_keys(static::definitions());
    }

    public static function scopeKeyFor(?int $branchId = null): string
    {
        return $branchId ? 'branch:'.$branchId : 'company';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->first();
    }
}
