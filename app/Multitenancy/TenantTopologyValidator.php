<?php

namespace App\Multitenancy;

use App\Models\AppServer;
use App\Models\StorageProfile;
use App\Models\StorageBucket;
use App\Models\StorageServer;
use App\Models\Tenant;
use App\Models\TenantDatabase;
use RuntimeException;

class TenantTopologyValidator
{
    public function __construct(
        private readonly TenantTopologyFingerprint $fingerprint,
        private readonly TenantRuntimeTopologyResolver $runtimeTopologies,
        private readonly TenantStorageTopologyResolver $storageTopologies
    ) {
    }

    public function validateTenant(Tenant $tenant): array
    {
        $tenant->loadMissing(['topology.database.server', 'runtimeTopology.appServer', 'storageTopologies.storageBucket.server']);

        $issues = [];

        if (!$tenant->topology) {
            $issues[] = 'missing database topology';
        } else {
            if ($tenant->topology->status !== 'active') {
                $issues[] = 'database topology inactive';
            }
            if (!$tenant->topology->server || $tenant->topology->server->status !== 'active') {
                $issues[] = 'database server inactive or missing';
            }
            if (!$tenant->topology->database || $tenant->topology->database->status !== 'active') {
                $issues[] = 'database mapping inactive or missing';
            }
            if (in_array($tenant->topology->isolation_mode, ['schema', 'database'], true) && trim((string) $tenant->topology->schema_name) === '') {
                $issues[] = 'schema name required for isolated topology';
            }
        }

        $runtime = $this->runtimeTopologies->resolveForTenant($tenant);
        if (!$runtime) {
            $issues[] = 'missing runtime topology';
        } else {
            if ($runtime->status !== 'active') {
                $issues[] = 'runtime topology inactive';
            }
            if (!$runtime->appServer || $runtime->appServer->status !== 'active') {
                $issues[] = 'app server inactive or missing';
            }
        }

        $publicStorage = $this->storageTopologies->resolveForTenant($tenant, 'public');
        $privateStorage = $this->storageTopologies->resolveForTenant($tenant, 'private');

        if (!$publicStorage) {
            $issues[] = 'missing active default public storage topology';
        }
        if (!$privateStorage) {
            $issues[] = 'missing active default private storage topology';
        }

        foreach ($tenant->storageTopologies as $storageTopology) {
            if ($storageTopology->status !== 'active') {
                $issues[] = 'inactive storage topology for ' . $storageTopology->visibility;
            }
            if (!$storageTopology->storageBucket || $storageTopology->storageBucket->status !== 'active') {
                $issues[] = 'storage bucket inactive or missing for ' . $storageTopology->visibility;
            }
            if (!$storageTopology->storageServer || $storageTopology->storageServer->status !== 'active') {
                $issues[] = 'storage server inactive or missing for ' . $storageTopology->visibility;
            }
        }

        if ($tenant->storageTopologies->where('visibility', 'public')->where('is_default', true)->count() > 1) {
            $issues[] = 'duplicate default public storage topologies';
        }
        if ($tenant->storageTopologies->where('visibility', 'private')->where('is_default', true)->count() > 1) {
            $issues[] = 'duplicate default private storage topologies';
        }

        return array_values(array_unique($issues));
    }

    public function validateDefaultInfrastructure(): array
    {
        $issues = [];

        $mainDatabase = TenantDatabase::query()->with('server')->where('key', 'main')->first();
        if (!$mainDatabase) {
            $issues[] = 'missing main tenant database';
        } elseif (!$mainDatabase->server || $mainDatabase->server->status !== 'active' || $mainDatabase->status !== 'active') {
            $issues[] = 'main tenant database infrastructure inactive';
        }

        $appServer = AppServer::query()->where('key', 'primary-app')->where('status', 'active')->first();
        if (!$appServer) {
            $issues[] = 'missing active primary-app runtime topology';
        }

        $storageServer = StorageServer::query()->where('key', 'primary-storage')->where('status', 'active')->first();
        if (!$storageServer) {
            $issues[] = 'missing active primary-storage server';
        }

        $publicBucket = StorageBucket::query()->where('key', 'public-default')->where('status', 'active')->first();
        $privateBucket = StorageBucket::query()->where('key', 'private-default')->where('status', 'active')->first();
        $publicProfile = StorageProfile::query()->where('visibility_scope', 'public')->active()->default()->first();
        $privateProfile = StorageProfile::query()->where('visibility_scope', 'private')->active()->default()->first();

        if (!$publicBucket) {
            $issues[] = 'missing active public-default storage bucket';
        }
        if (!$privateBucket) {
            $issues[] = 'missing active private-default storage bucket';
        }
        if (!$publicProfile) {
            $issues[] = 'missing active default public storage profile';
        }
        if (!$privateProfile) {
            $issues[] = 'missing active default private storage profile';
        }

        return $issues;
    }

    public function assertQueuedTopologySnapshot(Tenant $tenant, array $snapshot): void
    {
        $expected = [
            'tenant_id',
            'isolation_mode',
            'server_key',
            'database_key',
            'schema_name',
            'app_server_key',
            'queue_cluster',
            'storage_topology_fingerprint',
            'topology_fingerprint',
        ];

        foreach ($expected as $key) {
            if (!array_key_exists($key, $snapshot) || $snapshot[$key] === null || $snapshot[$key] === '') {
                throw new RuntimeException('Queued tenant job is missing topology snapshot field [' . $key . '].');
            }
        }

        if ((int) $snapshot['tenant_id'] !== (int) $tenant->getKey()) {
            throw new RuntimeException('Queued tenant job topology snapshot tenant mismatch.');
        }

        if ($this->fingerprint->combined($tenant) !== (string) $snapshot['topology_fingerprint']) {
            throw new RuntimeException('Queued tenant job topology snapshot is stale.');
        }
    }
}
