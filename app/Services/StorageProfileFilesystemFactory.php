<?php

namespace App\Services;

use App\Models\StorageProfile;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class StorageProfileFilesystemFactory
{
    public function build(StorageProfile $profile, array $snapshot = []): FilesystemAdapter
    {
        $driver = (string) ($snapshot['storage_driver'] ?? $profile->driver);

        return Storage::build(match ($driver) {
            's3' => $this->s3Config($profile, $snapshot),
            default => $this->localConfig($profile, $snapshot),
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function s3Config(StorageProfile $profile, array $snapshot): array
    {
        $bucket = (string) ($snapshot['storage_bucket'] ?? $profile->bucket ?? '');
        $region = (string) ($snapshot['storage_region'] ?? $profile->region ?? '');
        $endpoint = $this->nullableString($snapshot['storage_endpoint'] ?? $profile->endpoint);
        $url = $this->nullableString($snapshot['storage_url'] ?? $profile->url);

        return [
            'driver' => 's3',
            'key' => $profile->access_key_id ?: config('filesystems.disks.s3.key'),
            'secret' => $profile->secret_access_key ?: config('filesystems.disks.s3.secret'),
            'region' => $region !== '' ? $region : config('filesystems.disks.s3.region'),
            'bucket' => $bucket !== '' ? $bucket : config('filesystems.disks.s3.bucket'),
            'url' => $url,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => (bool) $profile->use_path_style_endpoint,
            'throw' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function localConfig(StorageProfile $profile, array $snapshot): array
    {
        $root = (string) ($snapshot['storage_root'] ?? $profile->root_path ?? storage_path('app/private'));
        $url = $this->nullableString($snapshot['storage_url'] ?? $profile->url);

        return [
            'driver' => 'local',
            'root' => $root,
            'url' => $url,
            'visibility' => $profile->visibility_scope === 'public' ? 'public' : 'private',
            'throw' => false,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
