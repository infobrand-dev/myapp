<?php

namespace App\Console\Commands;

use App\Models\TenantSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SubscriptionsCheckExpiry extends Command
{
    protected $signature = 'subscriptions:check-expiry
                            {--dry-run : Report without making any changes}';

    protected $description = 'Mark active subscriptions that have passed their end date as expired.';

    public function handle(): int
    {
        if (!Schema::hasTable('tenant_subscriptions')) {
            $this->warn('tenant_subscriptions table does not exist. Skipping.');
            return self::SUCCESS;
        }

        $query = TenantSubscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now());

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired subscriptions found.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] {$count} subscription(s) would be marked as expired.");

            $query->with('tenant:id,name,slug')->each(function ($sub) {
                $this->line("  - Tenant #{$sub->tenant_id} ({$sub->tenant?->name}), ended {$sub->ends_at->toDateString()}");
            });

            return self::SUCCESS;
        }

        $query->update(['status' => 'expired']);

        $this->info("{$count} subscription(s) marked as expired.");

        return self::SUCCESS;
    }
}
