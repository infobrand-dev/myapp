<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TenantResolver
{
    public function __construct(
        private readonly TenantTopologyValidator $validator
    ) {
    }

    public function resolve(Tenant $tenant): ResolvedTenant
    {
        $central = config('multitenancy.central_connection', 'central');

        if (!Schema::connection($central)->hasTable('tenant_topologies')) {
            return $this->defaultColumnResolvedTenant($tenant);
        }

        $tenant->loadMissing(['topology.database.server']);

        if (!$tenant->topology && config('multitenancy.runtime_mode', 'column') === 'column') {
            return $this->defaultColumnResolvedTenant($tenant);
        }

        $issues = $this->validator->validateTenant($tenant);
        if ($issues !== []) {
            throw new RuntimeException('Tenant topology is invalid: ' . implode('; ', $issues));
        }

        $topology = $tenant->topology;
        $database = $topology ? $topology->database : null;
        $server = $database ? $database->server : null;

        if (!$topology) {
            throw new RuntimeException('Tenant topology is missing.');
        }

        if ($topology->isolation_mode === 'tenant_id') {
            return new ResolvedTenant(
                $tenant,
                (string) config('database.default', 'pgsql'),
                (string) ($topology->schema_name ?: 'public'),
                (string) config('database.connections.' . config('database.default') . '.database', ''),
                (string) config('database.connections.' . config('database.default') . '.host', '127.0.0.1'),
                (int) config('database.connections.' . config('database.default') . '.port', 5432),
                (string) config('database.connections.' . config('database.default') . '.username', ''),
                (string) config('database.connections.' . config('database.default') . '.password', ''),
                (string) config('database.connections.' . config('database.default') . '.sslmode', 'prefer'),
                []
            );
        }

        if (!$database || !$server || !$topology->schema_name) {
            throw new RuntimeException('Tenant topology is incomplete. Missing database/server/schema mapping.');
        }

        return new ResolvedTenant(
            $tenant,
            (string) config('multitenancy.tenant_connection', 'tenant'),
            (string) $topology->schema_name,
            (string) $database->database_name,
            (string) $server->host,
            (int) ($server->port ?: 5432),
            $database->username,
            $database->decryptedPassword(),
            (string) ($database->sslmode ?: 'prefer'),
            is_array($database->options) ? $database->options : []
        );
    }

    private function defaultColumnResolvedTenant(Tenant $tenant): ResolvedTenant
    {
        return new ResolvedTenant(
            $tenant,
            (string) config('database.default', 'pgsql'),
            (string) ($tenant->schema_name ?: 'public'),
            (string) config('database.connections.' . config('database.default') . '.database', ''),
            (string) config('database.connections.' . config('database.default') . '.host', '127.0.0.1'),
            (int) config('database.connections.' . config('database.default') . '.port', 5432),
            (string) config('database.connections.' . config('database.default') . '.username', ''),
            (string) config('database.connections.' . config('database.default') . '.password', ''),
            (string) config('database.connections.' . config('database.default') . '.sslmode', 'prefer'),
            []
        );
    }
}
