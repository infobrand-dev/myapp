<?php

namespace App\Console\Commands;

use App\Modules\Sales\Models\Sale;
use App\Support\Commerce\CommerceOrderLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CommerceExpirePendingOrders extends Command
{
    protected $signature = 'commerce:expire-pending-orders
                            {--dry-run : Report without applying updates}';

    protected $description = 'Expire unpaid public commerce orders that have passed their payment window.';

    public function __construct(
        private readonly CommerceOrderLifecycleService $commerceOrders,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!Schema::hasTable('sales')) {
            $this->warn('sales table does not exist. Skipping.');

            return self::SUCCESS;
        }

        $query = Sale::query()
            ->where('source', Sale::SOURCE_ONLINE)
            ->where('payment_status', Sale::PAYMENT_UNPAID)
            ->orderBy('id');

        $matched = 0;
        $expired = 0;
        $dryRun = (bool) $this->option('dry-run');

        $query->chunkById(100, function ($sales) use (&$matched, &$expired, $dryRun): void {
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
                    $this->line(sprintf(
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

        if ($matched === 0) {
            $this->info('No pending commerce orders need to be expired.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("[dry-run] {$matched} pending commerce order(s) would be expired.");

            return self::SUCCESS;
        }

        $this->info("{$expired} pending commerce order(s) marked as expired.");

        return self::SUCCESS;
    }
}
