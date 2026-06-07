<?php

namespace App\Console\Commands;

use App\Contracts\CommercePendingOrderExpirer;
use Illuminate\Console\Command;

class CommerceExpirePendingOrders extends Command
{
    protected $signature = 'commerce:expire-pending-orders
                            {--dry-run : Report without applying updates}';

    protected $description = 'Expire unpaid public commerce orders that have passed their payment window.';

    public function __construct(
        private readonly CommercePendingOrderExpirer $expirer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $this->expirer->expirePending($dryRun, fn (string $message) => $this->line($message));
        $matched = (int) ($result['matched'] ?? 0);
        $expired = (int) ($result['expired'] ?? 0);

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
