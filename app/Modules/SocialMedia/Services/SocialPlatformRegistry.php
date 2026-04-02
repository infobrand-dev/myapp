<?php

namespace App\Modules\SocialMedia\Services;

use App\Modules\SocialMedia\Contracts\SocialPlatformAdapter;
use App\Modules\SocialMedia\Services\Platforms\FacebookDmPlatformAdapter;
use App\Modules\SocialMedia\Services\Platforms\InstagramDmPlatformAdapter;
use App\Modules\SocialMedia\Services\Platforms\ThreadsPlatformAdapter;
use App\Modules\SocialMedia\Services\Platforms\TikTokPlatformAdapter;
use App\Modules\SocialMedia\Services\Platforms\XPlatformAdapter;
use Illuminate\Support\Collection;

class SocialPlatformRegistry
{
    /**
     * @return Collection<int, SocialPlatformAdapter>
     */
    public function all(): Collection
    {
        return collect([
            app(FacebookDmPlatformAdapter::class),
            app(InstagramDmPlatformAdapter::class),
            app(ThreadsPlatformAdapter::class),
            app(XPlatformAdapter::class),
            app(TikTokPlatformAdapter::class),
        ]);
    }

    /**
     * @return Collection<int, SocialPlatformAdapter>
     */
    public function publicEnabled(): Collection
    {
        return $this->all()->filter(fn (SocialPlatformAdapter $adapter) => $adapter->publicEnabled())->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        $adapter = $this->all()->first(fn (SocialPlatformAdapter $candidate) => $candidate->key() === $key);

        return $adapter?->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function summary(): array
    {
        return $this->all()->map(fn (SocialPlatformAdapter $adapter) => $adapter->toArray())->values()->all();
    }
}
