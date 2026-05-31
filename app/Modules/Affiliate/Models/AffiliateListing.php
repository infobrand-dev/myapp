<?php

namespace App\Modules\Affiliate\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Products\Models\Product;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateListing extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'source_tenant_id',
        'source_product_id',
        'share_code',
        'status',
        'commission_type',
        'commission_rate',
        'landing_page_meta',
        'claimed_at',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'landing_page_meta' => 'array',
        'claimed_at' => 'datetime',
    ];

    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'source_tenant_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class)->latest('converted_at');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
