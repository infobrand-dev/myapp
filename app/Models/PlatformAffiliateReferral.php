<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAffiliateReferral extends Model
{
    protected $fillable = [
        'platform_affiliate_id',
        'tenant_id',
        'platform_plan_order_id',
        'referral_code',
        'buyer_email',
        'landing_path',
        'status',
        'order_amount',
        'order_currency',
        'commission_amount',
        'payout_status',
        'registered_at',
        'converted_at',
        'approved_at',
        'paid_at',
        'payout_reference',
        'payout_notes',
        'meta',
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'registered_at' => 'datetime',
        'converted_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(PlatformAffiliate::class, 'platform_affiliate_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PlatformPlanOrder::class, 'platform_plan_order_id');
    }
}
