<?php

namespace App\Modules\Purchases\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'purchase_request_id',
        'line_no',
        'product_id',
        'product_variant_id',
        'product_name_snapshot',
        'variant_name_snapshot',
        'sku_snapshot',
        'unit_snapshot',
        'product_snapshot',
        'notes',
        'qty',
        'unit_cost',
        'line_subtotal',
        'discount_total',
        'tax_total',
        'line_total',
        'pricing_snapshot',
        'sort_order',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'qty' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'line_total' => 'decimal:2',
        'pricing_snapshot' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Products\Models\Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Products\Models\ProductVariant::class, 'product_variant_id');
    }
}
