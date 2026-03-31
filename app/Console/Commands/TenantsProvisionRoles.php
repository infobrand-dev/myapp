<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\TenantRoleProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TenantsProvisionRoles extends Command
{
    protected $signature = 'tenants:provision-roles
                            {--tenant= : Provision a single tenant by ID}
                            {--force : Re-sync even if roles already exist}';

    protected $description = 'Create / sync default roles and permissions for all tenants (or one specific tenant).';

    public function handle(TenantRoleProvisioner $provisioner): int
    {
        if (!Schema::hasTable('tenants')) {
            $this->warn('tenants table does not exist — provisioning tenant 1 only.');
            $provisioner->ensureForTenant(1);
            $this->info('Done.');
            return self::SUCCESS;
        }

        $tenantId = $this->option('tenant');

        if ($tenantId !== null) {
            $tenant = Tenant::find((int) $tenantId);

            if (!$tenant) {
                $this->error("Tenant #{$tenantId} not found.");
                return self::FAILURE;
            }

            TenantContext::setCurrentId($tenant->id);
            $provisioner->ensureForTenant($tenant->id);
            TenantContext::forget();

            $this->info("Roles provisioned for tenant #{$tenant->id} ({$tenant->name}).");
            return self::SUCCESS;
        }

        $tenants = Tenant::query()->active()->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        foreach ($tenants as $tenant) {
            TenantContext::setCurrentId($tenant->id);
            $provisioner->ensureForTenant($tenant->id);
            TenantContext::forget();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Roles provisioned for {$tenants->count()} tenant(s).");

        return self::SUCCESS;
    }
}
