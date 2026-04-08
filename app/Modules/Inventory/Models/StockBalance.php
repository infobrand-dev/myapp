<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\NormalizesPgsqlBooleanAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBalance extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $table = 'inventory_stocks';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'stock_key',
        'product_id',
        'product_variant_id',
        'inventory_location_id',
        'current_quantity',
        'reserved_quantity',
        'minimum_quantity',
        'reorder_quantity',
        'allow_negative_stock',
        'last_movement_at',
        'meta',
    ];

    protected $casts = [
        'current_quantity' => 'decimal:4',
        'reserved_quantity' => 'decimal:4',
        'minimum_quantity' => 'decimal:4',
        'reorder_quantity' => 'decimal:4',
        'allow_negative_stock' => 'boolean',
        'last_movement_at' => 'datetime',
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

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'inventory_stock_id');
    }

    public function availableQuantity(): float
    {
        return (float) $this->current_quantity - (float) $this->reserved_quantity;
    }

    public function stockStatus(): string
    {
        $current = (float) $this->current_quantity;
        $minimum = (float) $this->minimum_quantity;

        if ($current <= 0) {
            return 'out_of_stock';
        }

        if ($minimum > 0 && $current <= $minimum) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
