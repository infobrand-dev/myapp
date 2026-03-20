<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    protected $table = 'inventory_stock_transfer_items';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'transfer_id',
        'product_id',
        'product_variant_id',
        'requested_quantity',
        'sent_quantity',
        'received_quantity',
        'transfer_out_movement_id',
        'transfer_in_movement_id',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:4',
        'sent_quantity' => 'decimal:4',
        'received_quantity' => 'decimal:4',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
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
