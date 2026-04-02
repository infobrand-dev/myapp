<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class ByoAiGuardService
{
    private const REQUESTS_PER_MINUTE = 60;
    private const MAX_CONCURRENT_REQUESTS = 3;
    private const CONCURRENCY_TTL_SECONDS = 120;

    public function acquire(int $tenantId): void
    {
        $rateKey = $this->rateKey($tenantId);
        if (RateLimiter::tooManyAttempts($rateKey, self::REQUESTS_PER_MINUTE)) {
            throw new RuntimeException('Batas request BYO AI tenant per menit sudah tercapai.');
        }

        RateLimiter::hit($rateKey, 60);

        $concurrencyKey = $this->concurrencyKey($tenantId);
        $current = (int) Cache::increment($concurrencyKey);
        Cache::put($concurrencyKey, $current, now()->addSeconds(self::CONCURRENCY_TTL_SECONDS));

        if ($current > self::MAX_CONCURRENT_REQUESTS) {
            $this->release($tenantId);

            throw new RuntimeException('Batas request BYO AI tenant yang berjalan bersamaan sudah tercapai.');
        }
    }

    public function release(int $tenantId): void
    {
        $concurrencyKey = $this->concurrencyKey($tenantId);
        $current = (int) Cache::get($concurrencyKey, 0);

        if ($current <= 1) {
            Cache::forget($concurrencyKey);

            return;
        }

        Cache::put($concurrencyKey, $current - 1, now()->addSeconds(self::CONCURRENCY_TTL_SECONDS));
    }

    private function rateKey(int $tenantId): string
    {
        return 'byo_ai_rpm:' . $tenantId;
    }

    private function concurrencyKey(int $tenantId): string
    {
        return 'byo_ai_concurrent:' . $tenantId;
    }
}
