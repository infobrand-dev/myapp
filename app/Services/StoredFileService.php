<?php

namespace App\Services;

use App\Models\StoredFile;
use App\Models\StoredFileAccessLog;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoredFileService
{
    public function __construct(
        private readonly StorageRoutingService $routing,
        private readonly StorageAccessService $access
    ) {
    }

    public function storeUploadedFile(
        UploadedFile $file,
        string $category,
        array $attributes = []
    ): StoredFile {
        $categoryConfig = (array) config("workspace-files.categories.{$category}", []);
        $visibility = (string) ($attributes['visibility'] ?? $categoryConfig['visibility'] ?? 'private');
        $directory = trim((string) ($attributes['directory'] ?? $categoryConfig['directory'] ?? $category), '/');
        $stored = $this->routing->storeUploadedFile($file, $category, array_merge($attributes, [
            'directory' => $directory,
            'visibility' => $visibility,
        ]));
        $categoryConfig = (array) config("workspace-files.categories.{$category}", []);

        return StoredFile::query()->create([
            'tenant_id' => $attributes['tenant_id'] ?? TenantContext::currentId(),
            'company_id' => $attributes['company_id'] ?? CompanyContext::currentId(),
            'branch_id' => array_key_exists('branch_id', $attributes) ? $attributes['branch_id'] : BranchContext::currentId(),
            'storage_profile_id' => optional($stored['storage_profile'] ?? null)->id,
            'disk' => (string) $stored['disk'],
            'directory' => $directory,
            'path' => (string) $stored['path'],
            'visibility' => $visibility,
            'availability_status' => 'available',
            'category' => $category,
            'access_class' => (string) ($attributes['access_class'] ?? $categoryConfig['access_class'] ?? ($visibility === 'public' ? 'public_asset' : 'private_document')),
            'share_strategy' => $this->nullableString($attributes['share_strategy'] ?? $categoryConfig['share_strategy'] ?? null),
            'retention_class' => $this->nullableString($attributes['retention_class'] ?? $categoryConfig['retention_class'] ?? null),
            'provider_origin' => $this->nullableString($attributes['provider_origin'] ?? null),
            'provider_media_id' => $this->nullableString($attributes['provider_media_id'] ?? null),
            'provider_media_url' => $this->nullableString($attributes['provider_media_url'] ?? null),
            'expires_at' => $attributes['expires_at'] ?? null,
            'origin_system' => (string) config('workspace-files.origin_system', config('app.name', 'app')),
            'origin_owner' => (string) config('workspace-files.origin_owner', 'first_party'),
            'source_module' => $attributes['source_module'] ?? null,
            'source_context' => $attributes['source_context'] ?? null,
            'storage_driver' => (string) ($stored['storage_driver'] ?? 'local'),
            'storage_bucket' => $this->nullableString($stored['storage_bucket'] ?? null),
            'storage_region' => $this->nullableString($stored['storage_region'] ?? null),
            'storage_endpoint' => $this->nullableString($stored['storage_endpoint'] ?? null),
            'storage_url' => $this->nullableString($stored['storage_url'] ?? null),
            'storage_root' => $this->nullableString($stored['storage_root'] ?? null),
            'storage_snapshot' => $stored['storage_snapshot'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'extension' => ($stored['extension'] ?? '') ?: null,
            'size_bytes' => max(0, (int) ($stored['size_bytes'] ?? $file->getSize() ?? 0)),
            'content_hash' => $stored['content_hash'] ?? null,
            'uploaded_by' => $attributes['uploaded_by'] ?? optional(auth()->user())->id,
            'meta' => array_merge([
                'captured_from' => 'first_party_upload',
                'server_side_storage' => true,
                'deduplicated' => (bool) ($stored['deduplicated'] ?? false),
                'legacy_storage_fallback' => (bool) ($stored['legacy'] ?? false),
                'storage_topology_degraded' => (bool) ($stored['legacy'] ?? false),
            ], (array) ($attributes['meta'] ?? [])),
        ]);
    }

    /**
     * @return array{response: \Symfony\Component\HttpFoundation\Response, result: string}
     */
    public function downloadResponse(StoredFile $storedFile, ?string $downloadName = null): array
    {
        return $this->access->download($storedFile, $downloadName);
    }

    /**
     * @return array{response: \Symfony\Component\HttpFoundation\Response, result: string}
     */
    public function previewResponse(StoredFile $storedFile, ?string $name = null): array
    {
        return $this->access->preview($storedFile, $name);
    }

    public function delete(?StoredFile $storedFile): void
    {
        if (!$storedFile) {
            return;
        }

        if ($storedFile->storage_profile_id && $storedFile->storageProfile) {
            try {
                $storage = app(StorageProfileFilesystemFactory::class)->build($storedFile->storageProfile, array_filter([
                    'storage_driver' => $storedFile->storage_driver,
                    'storage_bucket' => $storedFile->storage_bucket,
                    'storage_region' => $storedFile->storage_region,
                    'storage_endpoint' => $storedFile->storage_endpoint,
                    'storage_url' => $storedFile->storage_url,
                    'storage_root' => $storedFile->storage_root,
                ]));

                if ($storage->exists($storedFile->path)) {
                    $storage->delete($storedFile->path);
                }
            } catch (\Throwable) {
                // Keep metadata cleanup best-effort; audit trail stays in DB if physical delete fails.
            }
        } else {
            $storage = Storage::disk($storedFile->disk);
            if ($storage->exists($storedFile->path)) {
                $storage->delete($storedFile->path);
            }
        }

        $storedFile->delete();
    }

    public function deletePublicAssetByPath(?string $path, ?string $legacyDisk = null): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        $storedFile = StoredFile::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('path', $path)
            ->where('visibility', 'public')
            ->when($legacyDisk !== null && $legacyDisk !== '' && $legacyDisk !== 'public', fn ($query) => $query->where('disk', $legacyDisk))
            ->latest('id')
            ->first();

        if ($storedFile) {
            $this->delete($storedFile);

            return;
        }

        $disk = $legacyDisk ?: (string) config('workspace-files.public_disk', 'public');
        $storage = Storage::disk($disk);

        if ($storage->exists($path)) {
            $storage->delete($path);
        }
    }

    public function readContents(string $disk, ?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $storedFile = StoredFile::query()
            ->with('storageProfile')
            ->where('path', $path)
            ->when($disk !== '', fn ($query) => $query->where('disk', $disk))
            ->latest('id')
            ->first();

        try {
            if ($storedFile && $storedFile->storage_profile_id && $storedFile->storageProfile) {
                $storage = app(StorageProfileFilesystemFactory::class)->build($storedFile->storageProfile, array_filter([
                    'storage_driver' => $storedFile->storage_driver,
                    'storage_bucket' => $storedFile->storage_bucket,
                    'storage_region' => $storedFile->storage_region,
                    'storage_endpoint' => $storedFile->storage_endpoint,
                    'storage_url' => $storedFile->storage_url,
                    'storage_root' => $storedFile->storage_root,
                ]));

                if (!$storage->exists($path)) {
                    return null;
                }

                return (string) $storage->get($path);
            }

            $storage = Storage::disk($disk);
            if (!$storage->exists($path)) {
                return null;
            }

            return (string) $storage->get($path);
        } catch (\Throwable) {
            return null;
        }
    }

    public function logAccess(StoredFile $storedFile, Request $request, string $action, bool $authorized, array $meta = []): void
    {
        StoredFileAccessLog::query()->create([
            'stored_file_id' => $storedFile->id,
            'tenant_id' => $storedFile->tenant_id,
            'company_id' => $storedFile->company_id,
            'branch_id' => $storedFile->branch_id,
            'user_id' => optional($request->user())->id,
            'action' => $action,
            'was_authorized' => $authorized,
            'ip_address' => Str::limit((string) $request->ip(), 45, ''),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    public function logAccessFromContext(StoredFile $storedFile, string $action, bool $authorized, array $meta = []): void
    {
        StoredFileAccessLog::query()->create([
            'stored_file_id' => $storedFile->id,
            'tenant_id' => $storedFile->tenant_id,
            'company_id' => $storedFile->company_id,
            'branch_id' => $storedFile->branch_id,
            'user_id' => optional(auth()->user())->id,
            'action' => $action,
            'was_authorized' => $authorized,
            'ip_address' => null,
            'user_agent' => null,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    public function userCanAccess(StoredFile $storedFile, ?Authenticatable $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Super-admin')) {
            return true;
        }

        $permissions = (array) config("workspace-files.categories.{$storedFile->category}.permissions", []);
        if ($permissions === []) {
            return true;
        }

        if (method_exists($user, 'hasAnyPermission')) {
            return $user->hasAnyPermission($permissions);
        }

        return true;
    }

    public function ensureLegacyReference(string $disk, ?string $path, string $category, array $attributes = []): ?StoredFile
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $existing = StoredFile::query()
            ->where('tenant_id', $attributes['tenant_id'] ?? TenantContext::currentId())
            ->where('company_id', $attributes['company_id'] ?? CompanyContext::currentId())
            ->where('branch_id', array_key_exists('branch_id', $attributes) ? $attributes['branch_id'] : BranchContext::currentId())
            ->where('disk', $disk)
            ->where('path', $path)
            ->where('category', $category)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $categoryConfig = (array) config("workspace-files.categories.{$category}", []);

        return StoredFile::query()->create([
            'tenant_id' => $attributes['tenant_id'] ?? TenantContext::currentId(),
            'company_id' => $attributes['company_id'] ?? CompanyContext::currentId(),
            'branch_id' => array_key_exists('branch_id', $attributes) ? $attributes['branch_id'] : BranchContext::currentId(),
            'disk' => $disk,
            'directory' => $attributes['directory'] ?? dirname($path),
            'path' => $path,
            'visibility' => (string) ($categoryConfig['visibility'] ?? 'private'),
            'availability_status' => 'legacy_exposed',
            'category' => $category,
            'access_class' => (string) ($categoryConfig['access_class'] ?? 'private_document'),
            'share_strategy' => $this->nullableString($categoryConfig['share_strategy'] ?? null),
            'retention_class' => $this->nullableString($categoryConfig['retention_class'] ?? null),
            'origin_system' => (string) config('workspace-files.origin_system', config('app.name', 'app')),
            'origin_owner' => 'legacy_server_storage',
            'source_module' => $attributes['source_module'] ?? null,
            'source_context' => $attributes['source_context'] ?? null,
            'storage_driver' => (string) config("filesystems.disks.{$disk}.driver", 'local'),
            'storage_bucket' => $this->nullableString(config("filesystems.disks.{$disk}.bucket")),
            'storage_region' => $this->nullableString(config("filesystems.disks.{$disk}.region")),
            'storage_endpoint' => $this->nullableString(config("filesystems.disks.{$disk}.endpoint")),
            'storage_url' => $this->nullableString(config("filesystems.disks.{$disk}.url")),
            'storage_root' => $this->nullableString(config("filesystems.disks.{$disk}.root")),
            'original_name' => $attributes['original_name'] ?? basename($path),
            'mime_type' => $attributes['mime_type'] ?? null,
            'extension' => pathinfo($path, PATHINFO_EXTENSION) ?: null,
            'size_bytes' => Storage::disk($disk)->exists($path)
                ? max(0, (int) Storage::disk($disk)->size($path))
                : 0,
            'uploaded_by' => optional(auth()->user())->id,
            'meta' => array_merge([
                'captured_from' => 'legacy_path_materialized',
                'legacy_public_exposed' => true,
                'server_side_storage' => true,
            ], (array) ($attributes['meta'] ?? [])),
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
