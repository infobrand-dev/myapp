<?php

namespace App\Modules\Storefront\Adapters;

use App\Contracts\CommercePendingOrderExpirer;
use App\Modules\Sales\Models\Sale;
use App\Support\Commerce\CommerceOrderLifecycleService;
use Illuminate\Support\Facades\Schema;

class StorefrontPendingOrderExpirer implements CommercePendingOrderExpirer
{
    public function __construct(
        private readonly CommerceOrderLifecycleService $commerceOrders
    ) {
    }

    public function expirePending(bool $dryRun = false, ?callable $reporter = null): array
    {
        if (!Schema::hasTable('sales')) {
            return [
                'matched' => 0,
                'expired' => 0,
            ];
        }

        $query = Sale::query()
            ->where('source', Sale::SOURCE_ONLINE)
            ->where('payment_status', Sale::PAYMENT_UNPAID)
            ->orderBy('id');

        $matched = 0;
        $expired = 0;

        $query->chunkById(100, function ($sales) use (&$matched, &$expired, $dryRun, $reporter): void {
            foreach ($sales as $sale) {
                if (
                    !$this->commerceOrders->isCommerceOrder($sale)
                    || !$this->commerceOrders->isPaymentPending($sale)
                    || !$this->commerceOrders->isExpired($sale)
                ) {
                    continue;
                }

                $matched++;

                if ($dryRun) {
                    $reporter && $reporter(sprintf(
                        '[dry-run] Sale #%d %s would be expired.',
                        $sale->id,
                        $sale->sale_number ?: $sale->external_reference ?: '-'
                    ));
                    continue;
                }

                $this->commerceOrders->markExpired($sale);
                $expired++;
            }
        });

        return [
            'matched' => $matched,
            'expired' => $expired,
        ];
    }
}
