<?php

namespace App\Modules\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPriceLevel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'minimum_qty',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'minimum_qty' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_price_level_id');
    }
}
