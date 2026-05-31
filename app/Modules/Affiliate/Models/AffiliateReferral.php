<?php

namespace App\Modules\Affiliate\Models;

use App\Modules\Sales\Models\Sale;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateReferral extends Model
{
    protected $fillable = [
        'tenant_id',
        'affiliate_partner_id',
        'affiliate_listing_id',
        'affiliate_tenant_id',
        'affiliate_user_id',
        'source_product_id',
        'sale_id',
        'referral_code',
        'landing_url',
        'channel',
        'status',
        'commission_type',
        'commission_amount',
        'order_gross',
        'meta',
        'captured_at',
        'converted_at',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'order_gross' => 'decimal:2',
        'meta' => 'array',
        'captured_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'affiliate_partner_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(AffiliateListing::class, 'affiliate_listing_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
