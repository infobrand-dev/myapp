<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Multitenancy\TenantMigrationService;
use Illuminate\Console\Command;

class TenantSeedCommand extends Command
{
    protected $signature = 'tenant:seed {tenant : Tenant ID or slug}';

    protected $description = 'Run configured seeders for a tenant schema.';

    public function handle(TenantMigrationService $service): int
    {
        $value = (string) $this->argument('tenant');
        $tenant = ctype_digit($value)
            ? Tenant::query()->with(['database.server'])->find((int) $value)
            : Tenant::query()->with(['database.server'])->where('slug', $value)->first();

        if (!$tenant) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $service->seed($tenant);

        $this->info("Tenant [{$tenant->slug}] seeded.");

        return self::SUCCESS;
    }
}
