<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Multitenancy\TenantMigrationService;
use Illuminate\Console\Command;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate {tenant : Tenant ID or slug} {--seed} {--pretend}';

    protected $description = 'Run tenant migrations for a single tenant schema.';

    public function handle(TenantMigrationService $service): int
    {
        $tenant = $this->resolveTenant((string) $this->argument('tenant'));

        if (!$tenant) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $service->migrate($tenant, (bool) $this->option('seed'), (bool) $this->option('pretend'));
        $this->info("Tenant [{$tenant->slug}] migrated.");

        return self::SUCCESS;
    }

    private function resolveTenant(string $value): ?Tenant
    {
        return ctype_digit($value)
            ? Tenant::query()->with(['database.server'])->find((int) $value)
            : Tenant::query()->with(['database.server'])->where('slug', $value)->first();
    }
}
