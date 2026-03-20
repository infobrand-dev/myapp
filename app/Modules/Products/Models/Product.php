<?php

namespace App\Modules\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'type',
        'category_id',
        'brand_id',
        'unit_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'cost_price',
        'sell_price',
        'is_active',
        'track_stock',
        'featured_image_path',
        'meta',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'meta' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(ProductOptionGroup::class, 'product_id')->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->orderBy('position');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_id')->whereNull('product_variant_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class, 'product_id')->orderBy('sort_order');
    }

    public function gallery(): HasMany
    {
        return $this->media()->where('collection_name', 'gallery');
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
