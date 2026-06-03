<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Multitenancy\TenantMigrationService;
use Illuminate\Console\Command;

class TenantMigrateAllCommand extends Command
{
    protected $signature = 'tenant:migrate-all {--seed} {--pretend}';

    protected $description = 'Run tenant migrations for all active tenants.';

    public function handle(TenantMigrationService $service): int
    {
        foreach (Tenant::query()->where('status', 'active')->with(['database.server'])->orderBy('id')->cursor() as $tenant) {
            $this->line("Migrating {$tenant->slug} ({$tenant->schema_name})");
            $service->migrate($tenant, (bool) $this->option('seed'), (bool) $this->option('pretend'));
        }

        $this->info('Tenant migration sweep completed.');

        return self::SUCCESS;
    }
}
