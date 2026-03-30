<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCreditPricingSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'currency',
        'unit_tokens',
        'price_per_credit',
        'pack_options',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'unit_tokens' => 'integer',
        'price_per_credit' => 'integer',
        'pack_options' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];
}
