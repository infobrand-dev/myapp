<?php

namespace App\Modules\Affiliate\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliatePartner extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'referral_code',
        'commission_type',
        'commission_rate',
        'cookie_days',
        'is_active',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'cookie_days' => 'integer',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

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
