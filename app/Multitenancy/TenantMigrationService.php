<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Artisan;

class TenantMigrationService
{
    /** @var TenantMigrationManifest */
    private $manifest;

    public function __construct(TenantMigrationManifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function migrate(Tenant $tenant, bool $seed = false, bool $pretend = false): void
    {
        $resolved = app(TenantResolver::class)->resolve($tenant);
        app(TenantConnectionManager::class)->initialize($resolved);

        TenantContext::setResolvedTenant($resolved);

        try {
            if ($tenant->topology && $tenant->topology->isolation_mode === 'schema' && $tenant->topology->schema_name !== 'public') {
                \Illuminate\Support\Facades\DB::connection($resolved->connectionName)
                    ->statement('CREATE SCHEMA IF NOT EXISTS "' . str_replace('"', '""', $tenant->topology->schema_name) . '"');
            }

            foreach ($this->paths() as $path) {
                if (!is_dir($path) && !is_file($path)) {
                    continue;
                }

                Artisan::call('migrate', array_filter([
                    '--database' => $resolved->connectionName,
                    '--path' => $path,
                    '--realpath' => true,
                    '--force' => true,
                    '--pretend' => $pretend ?: null,
                ], static fn ($value) => $value !== null));
            }

            if ($seed) {
                $this->seed($resolved->tenant);
            }
        } finally {
            app(TenantConnectionManager::class)->purge();
            TenantContext::forget();
        }
    }

    public function seed(Tenant $tenant): void
    {
        $resolved = app(TenantResolver::class)->resolve($tenant);
        app(TenantConnectionManager::class)->initialize($resolved);
        TenantContext::setResolvedTenant($resolved);

        try {
            foreach ((array) config('multitenancy.tenant_seeders', []) as $seeder) {
                Artisan::call('db:seed', [
                    '--database' => $resolved->connectionName,
                    '--class' => $seeder,
                    '--force' => true,
                ]);
            }
        } finally {
            app(TenantConnectionManager::class)->purge();
            TenantContext::forget();
        }
    }

    /**
     * @return array<int, string>
     */
    public function paths(): array
    {
        return $this->manifest->allPaths();
    }
}
