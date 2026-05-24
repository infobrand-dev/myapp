<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSlugReservation extends Model
{
    protected $fillable = [
        'tenant_id',
        'slug',
        'source',
        'reserved_until',
        'released_at',
        'meta',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'reserved_until' => 'datetime',
        'released_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('released_at')
            ->where(function (Builder $query): void {
                $query->whereNull('reserved_until')
                    ->orWhere('reserved_until', '>', now());
            });
    }
}
