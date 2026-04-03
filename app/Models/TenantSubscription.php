<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'product_line',
        'status',
        'billing_provider',
        'billing_reference',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'auto_renews',
        'feature_overrides',
        'limit_overrides',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'auto_renews' => 'boolean',
        'feature_overrides' => 'array',
        'limit_overrides' => 'array',
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

    public function productLine(): string
    {
        $value = $this->product_line;
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return $this->plan?->productLine() ?: 'default';
    }

    public function scopeForProductLine(Builder $query, string $productLine): Builder
    {
        return $query->where('product_line', $productLine);
    }

    public function scopeCurrentForProductLine(Builder $query, int $tenantId, string $productLine): Builder
    {
        return $query
            ->where('tenant_id', $tenantId)
            ->forProductLine($productLine)
            ->active()
            ->latest('starts_at')
            ->latest('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where(function (Builder $builder): void {
                $builder->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }
}
