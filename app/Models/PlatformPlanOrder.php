<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlatformPlanOrder extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'product_line',
        'tenant_subscription_id',
        'order_number',
        'status',
        'amount',
        'currency',
        'billing_period',
        'buyer_email',
        'payment_channel',
        'starts_at',
        'ends_at',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'tenant_subscription_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PlatformInvoice::class);
    }

    public function affiliateReferral(): HasOne
    {
        return $this->hasOne(PlatformAffiliateReferral::class);
    }
}
