<?php

namespace App\Console\Commands;

use App\Models\TenantDatabase;
use Illuminate\Console\Command;

class TenantRestoreCommand extends Command
{
    protected $signature = 'tenant:restore {database_id : Target tenant_databases.id} {schema : Target schema name} {path : Input SQL file path}';

    protected $description = 'Print restore instructions for schema-level psql execution.';

    public function handle(): int
    {
        $database = TenantDatabase::query()->with('server')->find((int) $this->argument('database_id'));

        if (!$database || !$database->server) {
            $this->error('Target database not found.');

            return self::FAILURE;
        }

        $this->line(sprintf(
            'psql -h %s -p %d -U %s -d %s -f %s',
            $database->server->host,
            $database->server->port,
            $database->username ?: '<username>',
            $database->database_name,
            $this->argument('path')
        ));
        $this->line('Restore target schema: ' . $this->argument('schema'));

        return self::SUCCESS;
    }
}
