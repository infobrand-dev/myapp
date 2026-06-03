<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Multitenancy\TenantMigrationService;
use Illuminate\Console\Command;

class TenantMigrateSchemaCommand extends Command
{
    protected $signature = 'tenant:migrate-schema {tenant : Tenant ID or slug} {--seed}';

    protected $description = 'Run tenant schema/database migrations for a tenant already mapped to schema/database isolation.';

    public function handle(TenantMigrationService $service): int
    {
        $value = (string) $this->argument('tenant');
        $tenant = ctype_digit($value)
            ? Tenant::query()->with(['topology.database.server'])->find((int) $value)
            : Tenant::query()->with(['topology.database.server'])->where('slug', $value)->first();

        if (!$tenant || !$tenant->topology) {
            $this->error('Tenant topology not found.');

            return self::FAILURE;
        }

        if (!in_array($tenant->topology->isolation_mode, ['schema', 'database'], true)) {
            $this->error('Tenant is not in schema/database isolation mode.');

            return self::FAILURE;
        }

        $service->migrate($tenant, (bool) $this->option('seed'));
        $tenant->topology->update(['status' => 'active']);

        $this->info("Tenant [{$tenant->slug}] schema/database migrations completed.");

        return self::SUCCESS;
    }
}
