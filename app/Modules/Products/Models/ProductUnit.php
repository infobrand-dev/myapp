<?php

namespace App\Modules\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'precision',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'precision' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
}
