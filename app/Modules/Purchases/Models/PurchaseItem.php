<?php

namespace App\Modules\Purchases\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'purchase_id',
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
        'qty_received',
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
        'qty_received' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'line_total' => 'decimal:2',
        'pricing_snapshot' => 'array',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class);
    }

    public function remainingQty(): float
    {
        return max(0, round((float) $this->qty - (float) $this->qty_received, 4));
    }
}
