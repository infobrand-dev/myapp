<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentWorkflowRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'scope_key',
        'document_type',
        'requires_approval_before_conversion',
        'requires_approval_before_finalize',
        'notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'company_id' => 'integer',
        'branch_id' => 'integer',
        'requires_approval_before_conversion' => 'boolean',
        'requires_approval_before_finalize' => 'boolean',
    ];

    public static function definitions(): array
    {
        return [
            'sale' => [
                'label' => 'Sales Invoice',
                'default_requires_approval_before_conversion' => false,
                'default_requires_approval_before_finalize' => false,
            ],
            'purchase' => [
                'label' => 'Purchase Bill',
                'default_requires_approval_before_conversion' => false,
                'default_requires_approval_before_finalize' => false,
            ],
            'sale_quotation' => [
                'label' => 'Quotation',
                'default_requires_approval_before_conversion' => true,
                'default_requires_approval_before_finalize' => false,
            ],
            'sale_order' => [
                'label' => 'Sales Order',
                'default_requires_approval_before_conversion' => true,
                'default_requires_approval_before_finalize' => false,
            ],
            'purchase_request' => [
                'label' => 'Purchase Request',
                'default_requires_approval_before_conversion' => true,
                'default_requires_approval_before_finalize' => false,
            ],
            'purchase_order' => [
                'label' => 'Purchase Order',
                'default_requires_approval_before_conversion' => true,
                'default_requires_approval_before_finalize' => false,
            ],
        ];
    }

    public static function definition(string $documentType): array
    {
        return static::definitions()[$documentType] ?? [
            'label' => $documentType,
            'default_requires_approval_before_conversion' => false,
            'default_requires_approval_before_finalize' => false,
        ];
    }

    public static function supportedDocumentTypes(): array
    {
        return array_keys(static::definitions());
    }

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

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->first();
    }
}
