<?php

namespace App\Modules\Sales\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'sale_return_id',
        'sale_item_id',
        'line_no',
        'product_id',
        'product_variant_id',
        'product_name_snapshot',
        'variant_name_snapshot',
        'sku_snapshot',
        'barcode_snapshot',
        'unit_snapshot',
        'product_snapshot',
        'notes',
        'sale_qty_snapshot',
        'previous_returned_qty_snapshot',
        'qty_returned',
        'unit_price',
        'line_subtotal',
        'discount_total',
        'tax_total',
        'line_total',
        'pricing_snapshot',
        'sort_order',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'sale_qty_snapshot' => 'decimal:4',
        'previous_returned_qty_snapshot' => 'decimal:4',
        'qty_returned' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'line_total' => 'decimal:2',
        'pricing_snapshot' => 'array',
    ];

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
