<?php

namespace App\Multitenancy;

use App\Models\Tenant;

class TenantTopologyFingerprint
{
    public function database(Tenant $tenant): string
    {
        $tenant->loadMissing('topology.database.server');

        return $this->hash([
            'tenant_id' => $tenant->getKey(),
            'server_key' => optional($tenant->topology)->server_key,
            'database_key' => optional($tenant->topology)->database_key,
            'schema_name' => optional($tenant->topology)->schema_name,
            'isolation_mode' => optional($tenant->topology)->isolation_mode,
            'status' => optional($tenant->topology)->status,
        ]);
    }

    public function runtime(Tenant $tenant): string
    {
        $tenant->loadMissing('runtimeTopology.appServer');

        return $this->hash([
            'tenant_id' => $tenant->getKey(),
            'app_server_key' => optional($tenant->runtimeTopology)->app_server_key,
            'queue_cluster' => optional($tenant->runtimeTopology)->queue_cluster,
            'realtime_cluster' => optional($tenant->runtimeTopology)->realtime_cluster,
            'scheduler_cluster' => optional($tenant->runtimeTopology)->scheduler_cluster,
            'status' => optional($tenant->runtimeTopology)->status,
        ]);
    }

    public function storage(Tenant $tenant): string
    {
        $tenant->loadMissing('storageTopologies.storageBucket.server');

        return $this->hash(
            $tenant->storageTopologies
                ->sortBy(['visibility', 'storage_bucket_key', 'id'])
                ->map(fn ($item) => [
                    'id' => $item->getKey(),
                    'visibility' => $item->visibility,
                    'disk' => $item->disk,
                    'storage_server_key' => $item->storage_server_key,
                    'storage_bucket_key' => $item->storage_bucket_key,
                    'base_path' => $item->base_path,
                    'is_default' => $item->is_default,
                    'status' => $item->status,
                ])
                ->values()
                ->all()
        );
    }

    public function combined(Tenant $tenant): string
    {
        return $this->hash([
            'database' => $this->database($tenant),
            'runtime' => $this->runtime($tenant),
            'storage' => $this->storage($tenant),
        ]);
    }

    private function hash(array $payload): string
    {
        return sha1(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
