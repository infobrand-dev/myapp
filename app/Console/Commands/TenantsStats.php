<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Models\TenantSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TenantsStats extends Command
{
    protected $signature = 'tenants:stats';

    protected $description = 'Display a summary table of all tenants, their user count, and subscription status.';

    public function handle(): int
    {
        if (!Schema::hasTable('tenants')) {
            $this->warn('tenants table does not exist.');
            return self::SUCCESS;
        }

        $tenants = Tenant::query()
            ->withCount('users')
            ->with('activeSubscription.plan:id,name,code')
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');
            return self::SUCCESS;
        }

        $total     = $tenants->count();
        $active    = $tenants->where('is_active', true)->count();
        $subscribed = $tenants->filter(fn ($t) => $t->activeSubscription !== null)->count();

        $rows = $tenants->map(fn ($t) => [
            $t->id,
            $t->name,
            $t->slug,
            $t->is_active ? '<info>active</info>' : '<comment>inactive</comment>',
            $t->users_count,
            $t->activeSubscription
                ? ($t->activeSubscription->plan?->name ?? '—') . ' (' . ($t->activeSubscription->ends_at?->toDateString() ?? '∞') . ')'
                : '<comment>none</comment>',
        ])->toArray();

        $this->table(
            ['ID', 'Name', 'Slug', 'Status', 'Users', 'Subscription (expires)'],
            $rows
        );

        $this->newLine();
        $this->line("  Total: <info>{$total}</info>  |  Active: <info>{$active}</info>  |  Subscribed: <info>{$subscribed}</info>");

        return self::SUCCESS;
    }
}
