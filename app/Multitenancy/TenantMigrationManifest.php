<?php

namespace App\Multitenancy;

use Illuminate\Support\Facades\File;

class TenantMigrationManifest
{
    /**
     * @return array<int, string>
     */
    public function coreMigrationPaths(): array
    {
        return array_values(array_filter(array_map(function (string $migration): string {
            return database_path('migrations/' . $migration);
        }, config('multitenancy.tenant_core_migrations', [])), 'is_file'));
    }

    /**
     * @return array<int, string>
     */
    public function moduleMigrationPaths(): array
    {
        $paths = [];

        foreach ((array) config('multitenancy.tenant_module_migration_paths', []) as $path) {
            if (is_dir($path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return array<int, string>
     */
    public function allPaths(): array
    {
        return array_values(array_unique(array_merge(
            $this->coreMigrationPaths(),
            $this->moduleMigrationPaths(),
            array_values(array_filter((array) config('multitenancy.tenant_migration_paths', []), function ($path): bool {
                return is_file($path) || is_dir($path);
            }))
        )));
    }

    /**
     * @return array<int, string>
     */
    public function discoverModuleMigrationPaths(): array
    {
        $modulesPath = app_path('Modules');
        if (!is_dir($modulesPath)) {
            return [];
        }

        $paths = [];

        foreach (File::directories($modulesPath) as $modulePath) {
            $migrationPath = $modulePath . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';

            if (is_dir($migrationPath)) {
                $paths[] = $migrationPath;
            }
        }

        return array_values(array_unique($paths));
    }
}
