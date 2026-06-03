<?php

namespace App\Multitenancy;

use App\Models\StorageBucket;
use App\Models\StorageProfile;
use App\Models\StorageServer;
use App\Models\TenantStorageTopology;

class ResolvedTenantStorage
{
    public function __construct(
        public readonly TenantStorageTopology $topology,
        public readonly ?StorageProfile $profile,
        public readonly ?StorageBucket $bucket,
        public readonly ?StorageServer $server,
        public readonly string $disk,
        public readonly string $visibility,
        public readonly string $basePath,
        public readonly string $rootPath,
        public readonly string $bucketName,
        public readonly string $region,
        public readonly ?string $endpoint,
        public readonly ?string $url
    ) {
    }

    public function snapshot(): array
    {
        return [
            'tenant_storage_topology_id' => $this->topology->getKey(),
            'storage_profile_code' => $this->profile?->code,
            'storage_server_key' => $this->topology->storage_server_key,
            'storage_bucket_key' => $this->topology->storage_bucket_key,
            'storage_base_path' => $this->basePath,
            'storage_root' => $this->rootPath,
            'storage_bucket' => $this->bucketName,
            'storage_region' => $this->region,
            'storage_endpoint' => $this->endpoint,
            'storage_url' => $this->url,
        ];
    }
}
