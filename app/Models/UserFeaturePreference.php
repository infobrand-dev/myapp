<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFeaturePreference extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'product_line',
        'feature_key',
        'value',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
