<?php

namespace App\Modules\PointOfSale\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCartItem extends Model
{
    protected $fillable = [
        'uuid',
        'pos_cart_id',
        'line_no',
        'product_id',
        'product_variant_id',
        'barcode_scanned',
        'sku_snapshot',
        'barcode_snapshot',
        'product_name_snapshot',
        'variant_name_snapshot',
        'unit_name_snapshot',
        'qty',
        'unit_price',
        'manual_price_override',
        'discount_total',
        'tax_total',
        'line_total',
        'notes',
        'meta',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'manual_price_override' => 'boolean',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'line_total' => 'decimal:2',
        'meta' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(PosCart::class, 'pos_cart_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
