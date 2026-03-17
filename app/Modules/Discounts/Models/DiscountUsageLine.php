<?php

namespace App\Modules\Discounts\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountUsageLine extends Model
{
    protected $fillable = [
        'discount_usage_id',
        'discount_id',
        'voucher_id',
        'line_key',
        'product_id',
        'variant_id',
        'quantity',
        'subtotal_before',
        'discount_amount',
        'total_after',
        'snapshot',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'subtotal_before' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_after' => 'decimal:2',
        'snapshot' => 'array',
    ];

    public function usage(): BelongsTo
    {
        return $this->belongsTo(DiscountUsage::class, 'discount_usage_id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(DiscountVoucher::class, 'voucher_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
