<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformAffiliate extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'slug',
        'referral_code',
        'status',
        'commission_type',
        'commission_rate',
        'notes',
        'payout_meta',
        'meta',
        'click_count',
        'welcome_emailed_at',
        'last_clicked_at',
        'last_sale_at',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'payout_meta' => 'array',
        'meta' => 'array',
        'click_count' => 'integer',
        'welcome_emailed_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'last_sale_at' => 'datetime',
    ];

    public function referrals(): HasMany
    {
        return $this->hasMany(PlatformAffiliateReferral::class);
    }
}
