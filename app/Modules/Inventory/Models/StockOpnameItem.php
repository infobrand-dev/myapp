<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    protected $table = 'inventory_stock_opname_items';

    protected $fillable = [
        'opname_id',
        'inventory_stock_id',
        'product_id',
        'product_variant_id',
        'system_quantity',
        'physical_quantity',
        'difference_quantity',
        'final_system_quantity',
        'adjustment_quantity',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:4',
        'physical_quantity' => 'decimal:4',
        'difference_quantity' => 'decimal:4',
        'final_system_quantity' => 'decimal:4',
        'adjustment_quantity' => 'decimal:4',
    ];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'opname_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(StockBalance::class, 'inventory_stock_id');
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
