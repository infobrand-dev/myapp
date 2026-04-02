<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        $column = $query->qualifyColumn('is_active');

        if (DB::connection($this->getConnectionName())->getDriverName() === 'pgsql') {
            return $query->whereRaw($column . ' is true');
        }

        return $query->where('is_active', true);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function planOrders(): HasMany
    {
        return $this->hasMany(PlatformPlanOrder::class);
    }

    public function platformInvoices(): HasMany
    {
        return $this->hasMany(PlatformInvoice::class);
    }

    public function platformPayments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class);
    }

    public function aiCreditTransactions(): HasMany
    {
        return $this->hasMany(AiCreditTransaction::class);
    }

    public function byoAiRequests(): HasMany
    {
        return $this->hasMany(TenantByoAiRequest::class);
    }

    public function affiliateReferrals(): HasMany
    {
        return $this->hasMany(PlatformAffiliateReferral::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latestOfMany('starts_at');
    }
}
