<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSlugReservation;

class TenantSlugReservationService
{
    public function isReserved(string $slug): bool
    {
        return TenantSlugReservation::query()
            ->where('slug', $slug)
            ->active()
            ->exists();
    }

    public function reserveForTenant(Tenant $tenant, string $source = 'onboarding_pending', ?\DateTimeInterface $until = null, array $meta = []): TenantSlugReservation
    {
        return TenantSlugReservation::query()->updateOrCreate(
            ['slug' => $tenant->slug],
            [
                'tenant_id' => $tenant->id,
                'source' => $source,
                'reserved_until' => $until,
                'released_at' => null,
                'meta' => $meta,
            ]
        );
    }

    public function reserveDetached(string $slug, string $source, \DateTimeInterface $until, array $meta = []): TenantSlugReservation
    {
        return TenantSlugReservation::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'tenant_id' => null,
                'source' => $source,
                'reserved_until' => $until,
                'released_at' => null,
                'meta' => $meta,
            ]
        );
    }

    public function release(string $slug): void
    {
        TenantSlugReservation::query()
            ->where('slug', $slug)
            ->update([
                'released_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
