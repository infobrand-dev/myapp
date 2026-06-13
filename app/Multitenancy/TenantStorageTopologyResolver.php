<?php

namespace App\Multitenancy;

use App\Models\StorageProfile;
use App\Models\Tenant;
use App\Models\TenantStorageTopology;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use RuntimeException;

class TenantStorageTopologyResolver
{
    public function __construct(
        private readonly TenantTopologyFingerprint $fingerprint
    ) {
    }

    public function resolveCurrent(string $visibility, ?string $purpose = null): ?ResolvedTenantStorage
    {
        return $this->resolveForTenant(TenantContext::currentTenant(), $visibility, $purpose);
    }

    public function resolveForTenant(Tenant|int|null $tenant, string $visibility, ?string $purpose = null): ?ResolvedTenantStorage
    {
        $tenantModel = $this->resolveTenantModel($tenant);
        if (!$tenantModel) {
            return null;
        }

        $tenantModel->loadMissing(['storageTopologies.storageBucket.server']);

        $topology = $tenantModel->storageTopologies
            ->where('visibility', $visibility)
            ->where('status', 'active')
            ->first(fn (TenantStorageTopology $item) => $item->is_default);

        if (!$topology) {
            return null;
        }

        $profile = $this->resolveProfile($topology, $visibility, $purpose);
        $bucket = $topology->storageBucket;
        $server = $topology->storageServer ?: $bucket?->server;

        $bucketBasePath = trim((string) ($bucket?->base_path ?? ''), '/');
        $topologyBasePath = trim((string) $topology->base_path, '/');
        $effectiveBasePath = trim(implode('/', array_filter([$bucketBasePath, $topologyBasePath])), '/');

        $profileRoot = trim((string) ($profile?->root_path ?? ''), DIRECTORY_SEPARATOR . '/');
        $effectiveRoot = $profileRoot;

        if ($effectiveRoot !== '' && $bucketBasePath !== '') {
            $effectiveRoot .= DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $bucketBasePath);
        }

        return new ResolvedTenantStorage(
            $topology,
            $profile,
            $bucket,
            $server,
            (string) ($topology->disk ?: $bucket?->disk ?: $profile?->code ?: ($visibility === 'public' ? 'public' : 'private')),
            $visibility,
            $effectiveBasePath,
            $effectiveRoot,
            (string) ($bucket?->name ?: $profile?->bucket ?: ''),
            (string) ($bucket?->region ?: $server?->region ?: $profile?->region ?: ''),
            $this->nullableString($server?->endpoint ?? $profile?->endpoint),
            $this->nullableString($bucket?->cdn_url ?? $profile?->url)
        );
    }

    public function fingerprintForTenant(Tenant|int|null $tenant): string
    {
        $tenantModel = $this->resolveTenantModel($tenant);

        return $tenantModel ? $this->fingerprint->storage($tenantModel) : 'missing-storage-topology';
    }

    public function isConsumedByRoutingService(): bool
    {
        $contents = file_get_contents(app_path('Services/StorageRoutingService.php'));

        return is_string($contents) && str_contains($contents, TenantStorageTopologyResolver::class);
    }

    private function resolveTenantModel(Tenant|int|null $tenant): ?Tenant
    {
        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        if (is_int($tenant) && $tenant > 0) {
            return Tenant::query()->with(['storageTopologies.storageBucket.server'])->find($tenant);
        }

        return null;
    }

    private function resolveProfile(TenantStorageTopology $topology, string $visibility, ?string $purpose): ?StorageProfile
    {
        $profileCode = data_get($topology->meta, 'storage_profile_code');

        if (is_string($profileCode) && trim($profileCode) !== '') {
            return StorageProfile::query()
                ->where('code', trim($profileCode))
                ->active()
                ->first();
        }

        return $this->candidateProfiles($visibility, $purpose)
            ->sortBy([
                ['is_default', 'desc'],
                ['priority', 'asc'],
                ['id', 'asc'],
            ])
            ->first();
    }

    /**
     * @return Collection<int, StorageProfile>
     */
    private function candidateProfiles(string $visibility, ?string $purpose): Collection
    {
        return StorageProfile::query()
            ->where('visibility_scope', $visibility)
            ->active()
            ->get()
            ->filter(fn (StorageProfile $profile) => $profile->supportsPurpose($purpose))
            ->values();
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
