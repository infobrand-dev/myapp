<?php

namespace App\Services;

use App\Models\PlatformPlanOrder;
use App\Models\PlatformPromoCode;
use Illuminate\Support\Facades\DB;

class PlatformPromoCodeService
{
    public function markOrderPaid(PlatformPlanOrder $order): void
    {
        $this->syncUsageFlag($order, true);
    }

    public function releaseOrderUsage(PlatformPlanOrder $order): void
    {
        $this->syncUsageFlag($order, false);
    }

    private function syncUsageFlag(PlatformPlanOrder $order, bool $shouldCount): void
    {
        DB::transaction(function () use ($order, $shouldCount): void {
            $lockedOrder = PlatformPlanOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder) {
                return;
            }

            $meta = is_array($lockedOrder->meta) ? $lockedOrder->meta : [];
            $promoCode = strtoupper(trim((string) ($meta['promo_code'] ?? '')));
            $alreadyCounted = !empty($meta['promo_counted_at']);

            if ($promoCode === '') {
                return;
            }

            if ($shouldCount && $alreadyCounted) {
                return;
            }

            if (!$shouldCount && !$alreadyCounted) {
                return;
            }

            $promo = PlatformPromoCode::query()
                ->where('code', $promoCode)
                ->lockForUpdate()
                ->first();

            if (!$promo) {
                unset($meta['promo_counted_at']);
                $lockedOrder->forceFill(['meta' => $meta])->save();

                return;
            }

            if ($shouldCount) {
                $promo->incrementUsed();
                $meta['promo_counted_at'] = now()->toIso8601String();
            } else {
                $promo->decrementUsed();
                unset($meta['promo_counted_at']);
            }

            $lockedOrder->forceFill(['meta' => $meta])->save();
        });
    }
}
