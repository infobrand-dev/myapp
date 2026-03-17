<?php

namespace App\Modules\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'attribute_summary',
        'sku',
        'barcode',
        'cost_price',
        'sell_price',
        'wholesale_price',
        'member_price',
        'is_active',
        'track_stock',
        'position',
        'meta',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'member_price' => 'decimal:2',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'position' => 'integer',
        'meta' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_option_values',
            'product_variant_id',
            'product_option_value_id'
        )->withTimestamps();
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_variant_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class, 'product_variant_id');
    }
}
