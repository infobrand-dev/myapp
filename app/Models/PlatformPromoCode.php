<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformPromoCode extends Model
{
    protected $fillable = [
        'code',
        'label',
        'discount_percent',
        'applicable_product_lines',
        'is_active',
        'expires_at',
        'max_uses',
        'used_count',
    ];

    protected $casts = [
        'discount_percent' => 'integer',
        'applicable_product_lines' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        $col = $query->qualifyColumn('is_active');

        if (DB::connection($this->getConnectionName())->getDriverName() === 'pgsql') {
            return $query->whereRaw($col . ' is true');
        }

        return $query->where('is_active', true);
    }

    public function isUsable(?string $productLine = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && Carbon::now()->isAfter($this->expires_at)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($productLine !== null && is_array($this->applicable_product_lines) && ! in_array($productLine, $this->applicable_product_lines, true)) {
            return false;
        }

        return true;
    }

    public function applyDiscount(float $price): float
    {
        return round($price * (1 - $this->discount_percent / 100));
    }

    public function incrementUsed(): void
    {
        $this->increment('used_count');
    }

    public static function findByCode(string $code): ?self
    {
        $normalized = strtoupper(trim($code));

        return static::query()->where('code', $normalized)->first();
    }
}
