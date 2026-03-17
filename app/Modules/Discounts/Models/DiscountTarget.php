<?php

namespace App\Modules\Discounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountTarget extends Model
{
    protected $fillable = [
        'discount_id',
        'target_type',
        'target_id',
        'target_code',
        'operator',
        'sort_order',
        'payload',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'sort_order' => 'integer',
        'payload' => 'array',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
