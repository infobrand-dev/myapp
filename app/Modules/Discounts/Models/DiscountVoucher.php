<?php

namespace App\Modules\Discounts\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountVoucher extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $fillable = [
        'discount_id',
        'code',
        'description',
        'starts_at',
        'ends_at',
        'usage_limit',
        'usage_limit_per_customer',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class, 'voucher_id');
    }
}
