<?php

namespace App\Services;

use App\Multitenancy\TenantStorageTopologyResolver;
use App\Multitenancy\ResolvedTenantStorage;
use App\Models\StorageProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StorageRoutingService
{
    public function __construct(
        private readonly StorageProfileFilesystemFactory $filesystems,
        private readonly TenantStorageTopologyResolver $tenantStorageTopologies
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function storeUploadedFile(UploadedFile $file, string $category, array $attributes = []): array
    {
        $categoryConfig = $this->categoryConfig($category);
        $visibility = (string) ($attributes['visibility'] ?? $categoryConfig['visibility'] ?? 'private');
        $directory = trim((string) ($attributes['directory'] ?? $categoryConfig['directory'] ?? $category), '/');
        $legacyDisk = (string) ($attributes['disk'] ?? $categoryConfig['disk'] ?? $this->legacyDiskForVisibility($visibility));
        $purpose = (string) ($attributes['purpose'] ?? $categoryConfig['purpose'] ?? $category);
        $hash = hash_file('sha256', $file->getRealPath());
        $extension = $this->normalizeExtension($file);
        $resolvedStorage = $this->tenantStorageTopologies->resolveCurrent($visibility, $purpose);
        $path = $this->buildPath($this->resolveDirectory($resolvedStorage, $directory), $hash, $extension);
        $profiles = $resolvedStorage?->profile
            ? collect([$resolvedStorage->profile])
            : $this->candidateProfiles($visibility, $purpose);

        if ($profiles->isEmpty()) {
            return $this->storeOnLegacyDisk($file, $legacyDisk, $visibility, $path, $hash, $extension);
        }

        foreach ($this->orderedAttempts($profiles) as $profile) {
            try {
                return $this->storeOnProfile($file, $profile, $visibility, $path, $hash, $extension, $resolvedStorage);
            } catch (\Throwable $exception) {
                $profile->markWriteFailure($exception);
            }
        }

        return $this->storeOnLegacyDisk($file, $legacyDisk, $visibility, $path, $hash, $extension);
    }

    /**
     * @return Collection<int, StorageProfile>
     */
    public function candidateProfiles(string $visibility, ?string $purpose = null): Collection
    {
        return StorageProfile::ordered(
            StorageProfile::query()
                ->where('visibility_scope', $visibility)
                ->where('is_active', true)
                ->orderBy('priority')
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get()
                ->filter(fn (StorageProfile $profile) => $profile->supportsPurpose($purpose))
                ->values()
        );
    }

    /**
     * @param  Collection<int, StorageProfile>  $profiles
     * @return Collection<int, StorageProfile>
     */
    private function orderedAttempts(Collection $profiles): Collection
    {
        if ($profiles->count() <= 1) {
            return $profiles->values();
        }

        $ordered = StorageProfile::ordered($profiles);
        $selected = $this->weightedPick($ordered);

        return collect([$selected])->merge(
            $ordered->reject(fn (StorageProfile $profile) => $profile->is($selected))->values()
        )->values();
    }

    private function weightedPick(Collection $profiles): StorageProfile
    {
        $totalWeight = max(1, $profiles->sum(fn (StorageProfile $profile) => max(1, (int) $profile->weight)));
        $target = random_int(1, $totalWeight);
        $running = 0;

        foreach ($profiles as $profile) {
            $running += max(1, (int) $profile->weight);
            if ($target <= $running) {
                return $profile;
            }
        }

        return $profiles->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function storeOnProfile(
        UploadedFile $file,
        StorageProfile $profile,
        string $visibility,
        string $path,
        string $hash,
        string $extension,
        ?ResolvedTenantStorage $resolvedStorage = null
    ): array {
        $storage = $this->filesystems->build($profile, $this->profileSnapshot($resolvedStorage, $profile));
        $deduplicated = $storage->exists($path);

        if (!$deduplicated) {
            $stream = fopen($file->getRealPath(), 'rb');

            try {
                $storage->put($path, $stream, ['visibility' => $visibility]);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        return [
            'storage_profile' => $profile,
            'disk' => $profile->code,
            'path' => $path,
            'visibility' => $visibility,
            'storage_driver' => $profile->driver,
            'storage_bucket' => $resolvedStorage?->bucketName ?: $profile->bucket,
            'storage_region' => $resolvedStorage?->region ?: $profile->region,
            'storage_endpoint' => $resolvedStorage?->endpoint ?: $profile->endpoint,
            'storage_url' => $resolvedStorage?->url ?: $profile->url,
            'storage_root' => $resolvedStorage?->rootPath ?: $profile->root_path,
            'storage_snapshot' => array_merge([
                'storage_profile_code' => $profile->code,
                'weight' => $profile->weight,
                'priority' => $profile->priority,
            ], $resolvedStorage?->snapshot() ?? []),
            'content_hash' => $hash,
            'extension' => $extension,
            'size_bytes' => max(0, (int) ($file->getSize() ?? 0)),
            'deduplicated' => $deduplicated,
            'legacy' => false,
            'url' => $visibility === 'public' ? $this->publicUrl($storage, $path) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storeOnLegacyDisk(
        UploadedFile $file,
        string $disk,
        string $visibility,
        string $path,
        string $hash,
        string $extension
    ): array {
        $storage = Storage::disk($disk);
        $deduplicated = $storage->exists($path);

        if (!$deduplicated) {
            $stream = fopen($file->getRealPath(), 'rb');

            try {
                $storage->put($path, $stream, ['visibility' => $visibility]);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        return [
            'storage_profile' => null,
            'disk' => $disk,
            'path' => $path,
            'visibility' => $visibility,
            'storage_driver' => (string) config("filesystems.disks.{$disk}.driver", 'local'),
            'storage_bucket' => (string) config("filesystems.disks.{$disk}.bucket", ''),
            'storage_region' => (string) config("filesystems.disks.{$disk}.region", ''),
            'storage_endpoint' => (string) config("filesystems.disks.{$disk}.endpoint", ''),
            'storage_url' => (string) config("filesystems.disks.{$disk}.url", ''),
            'storage_root' => (string) config("filesystems.disks.{$disk}.root", ''),
            'storage_snapshot' => [
                'legacy_disk' => $disk,
            ],
            'content_hash' => $hash,
            'extension' => $extension,
            'size_bytes' => max(0, (int) ($file->getSize() ?? 0)),
            'deduplicated' => $deduplicated,
            'legacy' => true,
            'url' => $visibility === 'public' ? $this->publicUrl($storage, $path) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryConfig(string $category): array
    {
        return (array) config("workspace-files.categories.{$category}", []);
    }

    private function resolveDirectory(?ResolvedTenantStorage $resolvedStorage, string $directory): string
    {
        $directory = trim($directory, '/');

        if (!$resolvedStorage || $resolvedStorage->basePath === '') {
            return $directory;
        }

        return trim(implode('/', array_filter([$resolvedStorage->basePath, $directory])), '/');
    }

    private function legacyDiskForVisibility(string $visibility): string
    {
        return $visibility === 'public'
            ? (string) config('workspace-files.public_disk', 'public')
            : (string) config('workspace-files.private_disk', 'private');
    }

    private function buildPath(string $directory, string $hash, string $extension): string
    {
        return implode('/', array_filter([
            trim($directory, '/'),
            substr($hash, 0, 2),
            substr($hash, 2, 2),
            $hash . ($extension !== '' ? '.' . $extension : ''),
        ]));
    }

    private function normalizeExtension(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->guessExtension());

        if ($extension === '') {
            $extension = strtolower((string) $file->getClientOriginalExtension());
        }

        return preg_replace('/[^a-z0-9]+/', '', $extension) ?: '';
    }

    private function publicUrl(mixed $storage, string $path): ?string
    {
        try {
            $url = $storage->url($path);

            if (preg_match('/^https?:\/\//i', (string) $url)) {
                return $url;
            }

            return url((string) $url);
        } catch (\Throwable) {
            return null;
        }
    }

    private function profileSnapshot(?ResolvedTenantStorage $resolvedStorage, StorageProfile $profile): array
    {
        if (!$resolvedStorage) {
            return [];
        }

        return array_filter([
            'storage_driver' => $profile->driver,
            'storage_bucket' => $resolvedStorage->bucketName,
            'storage_region' => $resolvedStorage->region,
            'storage_endpoint' => $resolvedStorage->endpoint,
            'storage_url' => $resolvedStorage->url,
            'storage_root' => $resolvedStorage->rootPath,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
