<?php

namespace App\Console\Commands;

use App\Multitenancy\TenantProvisioner;
use Illuminate\Console\Command;

class TenantCreateCommand extends Command
{
    protected $signature = 'tenant:create
        {name : Tenant display name}
        {slug : Tenant slug}
        {--domain= : Primary custom domain or subdomain fqdn}
        {--plan= : Plan code}
        {--no-migrate : Skip tenant migrations when non-column runtime is active}
        {--no-seed : Skip tenant seeders when non-column runtime is active}';

    protected $description = 'Provision a tenant in the central registry using default topology primary/main/public/tenant_id.';

    public function handle(TenantProvisioner $provisioner): int
    {
        $tenant = $provisioner->create([
            'name' => (string) $this->argument('name'),
            'slug' => (string) $this->argument('slug'),
            'domain' => $this->option('domain'),
            'plan' => $this->option('plan'),
        ], !(bool) $this->option('no-migrate'), !(bool) $this->option('no-seed'));

        $this->table(['ID', 'UUID', 'Slug', 'Isolation', 'Schema', 'Database Key', 'Server Key', 'Status'], [[
            $tenant->id,
            $tenant->uuid,
            $tenant->slug,
            optional($tenant->topology)->isolation_mode,
            optional($tenant->topology)->schema_name,
            optional($tenant->topology)->database_key,
            optional($tenant->topology)->server_key,
            $tenant->status,
        ]]);

        return self::SUCCESS;
    }
}
