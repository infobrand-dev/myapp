<?php

namespace App\Multitenancy;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantConnectionManager
{
    private ?ResolvedTenant $resolvedTenant = null;
    private ?string $originalDefaultConnection = null;

    public function initialize(ResolvedTenant $resolvedTenant): void
    {
        if (!$this->shouldSwitchRuntimeConnection($resolvedTenant)) {
            $this->resolvedTenant = $resolvedTenant;

            return;
        }

        if (optional($resolvedTenant->tenant->topology)->status !== 'active') {
            throw new RuntimeException('Tenant runtime connection cannot be initialized for inactive topology.');
        }

        $connectionName = config('multitenancy.tenant_connection', 'tenant');
        $baseConfig = Config::get('database.connections.' . $connectionName);

        if (!is_array($baseConfig)) {
            throw new RuntimeException("Tenant connection [{$connectionName}] is not configured.");
        }

        $config = array_merge($baseConfig, [
            'host' => $resolvedTenant->host,
            'port' => $resolvedTenant->port,
            'database' => $resolvedTenant->databaseName,
            'username' => $resolvedTenant->username,
            'password' => $resolvedTenant->password,
            'schema' => $resolvedTenant->schemaName,
            'sslmode' => $resolvedTenant->sslmode,
        ]);

        if ($resolvedTenant->options !== []) {
            $config['options'] = array_merge($baseConfig['options'] ?? [], $resolvedTenant->options);
        }

        Config::set('database.connections.' . $connectionName, $config);
        $this->originalDefaultConnection ??= (string) Config::get('database.default', 'pgsql');
        Config::set('database.default', $connectionName);

        DB::disconnect($this->originalDefaultConnection);
        DB::purge($connectionName);

        $connection = DB::connection($connectionName);
        $schema = $this->quoteIdentifier($resolvedTenant->schemaName);
        $connection->statement("SET search_path TO {$schema}, public");

        $this->resolvedTenant = $resolvedTenant;
    }

    public function current(): ?ResolvedTenant
    {
        return $this->resolvedTenant;
    }

    public function purge(): void
    {
        if (!$this->shouldSwitchRuntimeConnection($this->resolvedTenant)) {
            $this->resolvedTenant = null;

            return;
        }

        $connectionName = config('multitenancy.tenant_connection', 'tenant');
        DB::disconnect($connectionName);
        DB::purge($connectionName);
        if ($this->originalDefaultConnection !== null) {
            Config::set('database.default', $this->originalDefaultConnection);
        }
        $this->resolvedTenant = null;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    public function shouldSwitchRuntimeConnection(?ResolvedTenant $resolvedTenant = null): bool
    {
        $runtimeMode = config('multitenancy.runtime_mode', 'column');

        if (!in_array($runtimeMode, ['schema', 'database'], true)) {
            return false;
        }

        $topologyMode = optional(optional($resolvedTenant)->tenant->topology)->isolation_mode;

        if (!in_array($topologyMode, ['schema', 'database'], true)) {
            return false;
        }

        return $topologyMode === $runtimeMode || ($runtimeMode === 'database' && $topologyMode === 'schema');
    }
}
