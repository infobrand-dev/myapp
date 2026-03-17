<?php

namespace App\Modules\Discounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountUsage extends Model
{
    protected $fillable = [
        'discount_id',
        'voucher_id',
        'usage_reference_type',
        'usage_reference_id',
        'customer_reference_type',
        'customer_reference_id',
        'outlet_reference',
        'sales_channel',
        'usage_status',
        'currency_code',
        'subtotal_before',
        'discount_total',
        'grand_total_after',
        'evaluated_at',
        'applied_at',
        'snapshot',
        'meta',
    ];

    protected $casts = [
        'subtotal_before' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total_after' => 'decimal:2',
        'evaluated_at' => 'datetime',
        'applied_at' => 'datetime',
        'snapshot' => 'array',
        'meta' => 'array',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(DiscountVoucher::class, 'voucher_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DiscountUsageLine::class)->orderBy('id');
    }
}
