<?php

namespace App\Modules\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'attribute_summary',
        'sku',
        'barcode',
        'cost_price',
        'sell_price',
        'is_active',
        'track_stock',
        'position',
        'meta',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
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

    public function wholesalePrice(): ?float
    {
        return $this->priceForLevelCode('wholesale');
    }

    public function memberPrice(): ?float
    {
        return $this->priceForLevelCode('member');
    }

    public function getWholesalePriceAttribute(): ?float
    {
        return $this->wholesalePrice();
    }

    public function getMemberPriceAttribute(): ?float
    {
        return $this->memberPrice();
    }

    private function priceForLevelCode(string $code): ?float
    {
        $price = $this->priceRows()
            ->first(fn (ProductPrice $item) => $item->priceLevel?->code === $code);

        return $price ? (float) $price->price : null;
    }

    private function priceRows(): Collection
    {
        $relation = $this->relationLoaded('prices')
            ? $this->getRelation('prices')
            : $this->prices()->with('priceLevel')->get();

        return $relation instanceof Collection ? $relation : collect($relation);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', 1)
            ->firstOrFail();
    }
}
