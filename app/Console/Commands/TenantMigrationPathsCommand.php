<?php

namespace App\Console\Commands;

use App\Multitenancy\TenantMigrationManifest;
use Illuminate\Console\Command;

class TenantMigrationPathsCommand extends Command
{
    protected $signature = 'tenant:migration-paths';

    protected $description = 'Show core and module migration paths that will be applied to tenant schema/database runtime.';

    public function handle(TenantMigrationManifest $manifest): int
    {
        $rows = [];

        foreach ($manifest->coreMigrationPaths() as $path) {
            $rows[] = ['core', $path];
        }

        foreach ($manifest->moduleMigrationPaths() as $path) {
            $rows[] = ['module', $path];
        }

        foreach ((array) config('multitenancy.tenant_migration_paths', []) as $path) {
            if (is_file($path) || is_dir($path)) {
                $rows[] = ['extra', $path];
            }
        }

        $this->table(['Type', 'Path'], $rows);

        return self::SUCCESS;
    }
}
