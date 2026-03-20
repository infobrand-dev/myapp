<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'billing_interval',
        'is_active',
        'is_public',
        'is_system',
        'sort_order',
        'features',
        'limits',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
        'limits' => 'array',
        'meta' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
