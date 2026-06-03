<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use App\Models\TenantDatabase;
use App\Models\TenantDomain;
use App\Models\TenantRuntimeTopology;
use App\Models\TenantStorageTopology;
use App\Models\TenantTopology;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TenantProvisioner
{
    public function __construct(
        private readonly TenantTopologyValidator $validator
    ) {
    }

    public function create(array $attributes, bool $migrate = true, bool $seed = true): Tenant
    {
        $issues = $this->validator->validateDefaultInfrastructure();
        if ($issues !== []) {
            throw new RuntimeException('Default tenant topology infrastructure is incomplete: ' . implode('; ', $issues));
        }

        return DB::connection(config('multitenancy.central_connection', 'central'))->transaction(function () use ($attributes, $migrate, $seed): Tenant {
            $database = $this->mainDatabase();
            $schemaName = 'public';

            $tenant = Tenant::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $attributes['name'],
                'slug' => $attributes['slug'],
                'is_active' => true,
                'status' => 'active',
                'plan' => $attributes['plan'] ?? null,
                'server_id' => $database->server_id,
                'database_id' => $database->id,
                'schema_name' => $schemaName,
                'meta' => $attributes['meta'] ?? null,
            ]);

            if (!empty($attributes['domain'])) {
                TenantDomain::query()->create([
                    'tenant_id' => $tenant->id,
                    'domain' => strtolower((string) $attributes['domain']),
                    'is_primary' => true,
                    'status' => 'active',
                ]);
            }

            TenantTopology::query()->create([
                'tenant_id' => $tenant->id,
                'tenant_server_id' => $database->server_id,
                'tenant_database_id' => $database->id,
                'server_key' => optional($database->server)->key ?: 'primary',
                'database_key' => $database->key ?: 'main',
                'schema_name' => $schemaName,
                'isolation_mode' => 'tenant_id',
                'status' => 'active',
                'meta' => [
                    'ready_for_schema_mode' => false,
                    'ready_for_database_mode' => false,
                ],
            ]);

            TenantRuntimeTopology::query()->create([
                'tenant_id' => $tenant->id,
                'app_server_id' => \App\Models\AppServer::query()->where('key', 'primary-app')->value('id'),
                'app_server_key' => 'primary-app',
                'queue_cluster' => 'default',
                'realtime_cluster' => 'default',
                'scheduler_cluster' => 'default',
                'status' => 'active',
            ]);

            $storageServerId = \App\Models\StorageServer::query()->where('key', 'primary-storage')->value('id');
            $publicBucketId = \App\Models\StorageBucket::query()->where('key', 'public-default')->value('id');
            $privateBucketId = \App\Models\StorageBucket::query()->where('key', 'private-default')->value('id');

            TenantStorageTopology::query()->create([
                'tenant_id' => $tenant->id,
                'storage_server_id' => $storageServerId,
                'storage_bucket_id' => $publicBucketId,
                'storage_server_key' => 'primary-storage',
                'storage_bucket_key' => 'public-default',
                'disk' => config('workspace-files.public_disk', 'public'),
                'visibility' => 'public',
                'base_path' => 'tenants/' . $tenant->id . '/public',
                'is_default' => true,
                'status' => 'active',
            ]);

            TenantStorageTopology::query()->create([
                'tenant_id' => $tenant->id,
                'storage_server_id' => $storageServerId,
                'storage_bucket_id' => $privateBucketId,
                'storage_server_key' => 'primary-storage',
                'storage_bucket_key' => 'private-default',
                'disk' => config('workspace-files.private_disk', 'private'),
                'visibility' => 'private',
                'base_path' => 'tenants/' . $tenant->id . '/private',
                'is_default' => true,
                'status' => 'active',
            ]);

            if ($migrate && config('multitenancy.runtime_mode', 'column') !== 'column') {
                app(TenantMigrationService::class)->migrate($tenant->fresh(['topology.database.server']), $seed);
            }

            $tenant = $tenant->fresh(['topology.database.server', 'runtimeTopology.appServer', 'storageTopologies.storageBucket.server', 'domains']);
            $issues = $this->validator->validateTenant($tenant);

            if ($issues !== []) {
                throw new RuntimeException('Provisioned tenant topology failed validation: ' . implode('; ', $issues));
            }

            return $tenant;
        });
    }

    public function pickDatabase(): TenantDatabase
    {
        $database = TenantDatabase::query()
            ->with('server')
            ->where('status', 'active')
            ->get()
            ->filter(function (TenantDatabase $database): bool {
                return $database->server && $database->server->status === 'active'
                    && $database->current_schemas < $database->max_schemas;
            })
            ->sortBy(fn (TenantDatabase $database) => ($database->current_schemas / max($database->max_schemas, 1)))
            ->first();

        if (!$database) {
            throw new RuntimeException('No active tenant database has available schema capacity.');
        }

        return $database;
    }

    public function mainDatabase(): TenantDatabase
    {
        $database = TenantDatabase::query()
            ->with('server')
            ->where('key', 'main')
            ->first();

        if (!$database) {
            throw new RuntimeException('Main tenant database topology is not configured.');
        }

        return $database;
    }
}
