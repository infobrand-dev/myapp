<?php

namespace App\Modules\Products\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'product_variant_id',
        'product_price_level_id',
        'currency_code',
        'price',
        'minimum_qty',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'minimum_qty' => 'decimal:4',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function priceLevel(): BelongsTo
    {
        return $this->belongsTo(ProductPriceLevel::class, 'product_price_level_id');
    }
}
