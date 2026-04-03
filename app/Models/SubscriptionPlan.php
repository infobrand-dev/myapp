<?php

namespace App\Models;

use App\Support\PlanProductLineMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    public function scopeActive(Builder $query): Builder
    {
        $column = $query->qualifyColumn('is_active');

        if (DB::connection($this->getConnectionName())->getDriverName() === 'pgsql') {
            return $query->whereRaw($column . ' is true');
        }

        return $query->where('is_active', true);
    }

    public function scopePublic(Builder $query): Builder
    {
        $column = $query->qualifyColumn('is_public');

        if (DB::connection($this->getConnectionName())->getDriverName() === 'pgsql') {
            return $query->whereRaw($column . ' is true');
        }

        return $query->where('is_public', true);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function productLine(): ?string
    {
        $value = $this->meta['product_line'] ?? null;

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return PlanProductLineMap::canonicalProductLine(trim($value));
    }

    public function productLineLabel(): ?string
    {
        return match ($this->productLine()) {
            'omnichannel' => 'Omnichannel',
            'crm' => 'CRM',
            'accounting', 'commerce' => 'Accounting',
            'project_management' => 'Project Management',
            'internal' => 'Internal',
            default => $this->productLine() ? str($this->productLine())->replace('_', ' ')->title()->toString() : null,
        };
    }

    public function displayName(): string
    {
        $productLine = $this->productLineLabel();
        $name = trim((string) $this->name);

        if (!$productLine) {
            return $name;
        }

        if (strtolower($name) === strtolower($productLine) || str_starts_with(strtolower($name), strtolower($productLine) . ' ')) {
            return $name;
        }

        return trim($productLine . ' ' . $name);
    }

    public function billingIntervalLabel(): string
    {
        return match ((string) $this->billing_interval) {
            'monthly' => 'Bulanan',
            'semiannual', 'biannual', 'half_yearly', '6_months', '6-months' => '6 Bulanan',
            'yearly', 'annual' => 'Tahunan',
            '' => 'Custom',
            default => str((string) $this->billing_interval)->replace(['_', '-'], ' ')->title()->toString(),
        };
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->displayName();
    }

    public function getBillingIntervalLabelAttribute(): string
    {
        return $this->billingIntervalLabel();
    }
}
