<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $table = 'inventory_stock_movements';

    protected $fillable = [
        'stock_key',
        'inventory_stock_id',
        'product_id',
        'product_variant_id',
        'inventory_location_id',
        'movement_type',
        'direction',
        'quantity',
        'before_quantity',
        'after_quantity',
        'reference_type',
        'reference_id',
        'reason_code',
        'reason_text',
        'occurred_at',
        'performed_by',
        'approved_by',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'before_quantity' => 'decimal:4',
        'after_quantity' => 'decimal:4',
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

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

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
