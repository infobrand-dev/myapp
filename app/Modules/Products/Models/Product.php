<?php

namespace App\Modules\Products\Models;

use App\Modules\Contacts\Models\Contact;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use NormalizesPgsqlBooleanAttributes;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'type',
                'category_id',
                'brand_id',
                'unit_id',
                'default_supplier_contact_id',
                'name',
                'sku',
                'barcode',
                'description',
                'cost_price',
                'sell_price',
                'minimum_stock',
                'reorder_point',
                'is_active',
                'track_stock',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('product');
    }

    protected $fillable = [
        'tenant_id',
        'type',
        'category_id',
        'brand_id',
        'unit_id',
        'default_supplier_contact_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'cost_price',
        'sell_price',
        'minimum_stock',
        'reorder_point',
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
        'minimum_stock' => 'decimal:4',
        'reorder_point' => 'decimal:4',
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

    public function defaultSupplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'default_supplier_contact_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
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

    public function getMarginAmountAttribute(): float
    {
        return round((float) $this->sell_price - (float) $this->cost_price, 2);
    }

    public function getMarginPercentAttribute(): ?float
    {
        $sellPrice = (float) $this->sell_price;

        if ($sellPrice <= 0) {
            return null;
        }

        return round(($this->margin_amount / $sellPrice) * 100, 2);
    }

    private function priceForLevelCode(string $code): ?float
    {
        $price = $this->priceRows()
            ->first(function (ProductPrice $item) use ($code) {
                return optional($item->priceLevel)->code === $code;
            });

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
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
