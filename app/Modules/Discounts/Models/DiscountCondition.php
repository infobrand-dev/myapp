<?php

namespace App\Modules\Discounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountCondition extends Model
{
    protected $fillable = [
        'discount_id',
        'condition_type',
        'operator',
        'value_type',
        'value',
        'secondary_value',
        'sort_order',
        'payload',
    ];

    protected $casts = [
        'secondary_value' => 'decimal:4',
        'sort_order' => 'integer',
        'payload' => 'array',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
