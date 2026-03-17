<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpeningItem extends Model
{
    protected $table = 'inventory_stock_opening_items';

    protected $fillable = [
        'opening_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'minimum_quantity',
        'reorder_quantity',
        'movement_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'minimum_quantity' => 'decimal:4',
        'reorder_quantity' => 'decimal:4',
    ];

    public function opening(): BelongsTo
    {
        return $this->belongsTo(StockOpening::class, 'opening_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'movement_id');
    }
}
