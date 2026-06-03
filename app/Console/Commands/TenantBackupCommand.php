<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantBackupCommand extends Command
{
    protected $signature = 'tenant:backup {tenant : Tenant ID or slug} {path : Output SQL file path}';

    protected $description = 'Print backup instructions for schema-level pg_dump execution.';

    public function handle(): int
    {
        $value = (string) $this->argument('tenant');
        $tenant = ctype_digit($value)
            ? Tenant::query()->with(['database.server'])->find((int) $value)
            : Tenant::query()->with(['database.server'])->where('slug', $value)->first();

        if (!$tenant || !$tenant->database || !$tenant->database->server) {
            $this->error('Tenant registry is incomplete.');

            return self::FAILURE;
        }

        $this->line(sprintf(
            'pg_dump -h %s -p %d -U %s -d %s -n %s -f %s',
            $tenant->database->server->host,
            $tenant->database->server->port,
            $tenant->database->username ?: '<username>',
            $tenant->database->database_name,
            $tenant->schema_name,
            $this->argument('path')
        ));

        return self::SUCCESS;
    }
}
