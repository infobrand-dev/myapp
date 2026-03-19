<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $table = 'inventory_stock_adjustment_items';

    protected $fillable = [
        'adjustment_id',
        'product_id',
        'product_variant_id',
        'direction',
        'quantity',
        'movement_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
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
