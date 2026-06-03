<?php

namespace App\Services;

use App\Models\StoredFile;
use App\Models\StorageProfile;
use App\Support\TenantContext;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Facades\Storage;

class StorageAccessService
{
    public function __construct(
        private readonly StorageProfileFilesystemFactory $filesystems,
        private readonly ResponseFactory $responseFactory
    ) {
    }

    /**
     * @return array{response: \Symfony\Component\HttpFoundation\Response, result: string}
     */
    public function download(StoredFile $storedFile, ?string $downloadName = null): array
    {
        return $this->respond($storedFile, true, $downloadName);
    }

    /**
     * @return array{response: \Symfony\Component\HttpFoundation\Response, result: string}
     */
    public function preview(StoredFile $storedFile, ?string $name = null): array
    {
        return $this->respond($storedFile, false, $name);
    }

    /**
     * @return array{response: \Symfony\Component\HttpFoundation\Response, result: string}
     */
    public function legacyPreview(string $disk, string $path, ?string $name = null): array
    {
        return [
            'response' => Storage::disk($disk)->response(
                $path,
                $name ?: basename($path),
                ['Content-Disposition' => 'inline; filename="' . addslashes($name ?: basename($path)) . '"']
            ),
            'result' => 'legacy_preview_success',
        ];
    }

    public function legacySensitiveDownloadUrl(string $disk, string $path, string $category, ?string $name = null): ?string
    {
        $path = trim($path);
        if ($path === '' || !auth()->check()) {
            return null;
        }

        return \Illuminate\Support\Facades\URL::temporarySignedRoute('stored-files.legacy-download', now()->addMinutes(10), [
            'disk' => $disk,
            'path' => $path,
            'category' => $category,
            'name' => $name,
            'issued_to' => auth()->id(),
        ]);
    }

    private function respond(StoredFile $storedFile, bool $download, ?string $downloadName = null): array
    {
        if (!$storedFile->storage_profile_id) {
            return [
                'response' => $download
                    ? Storage::disk($storedFile->disk)->download(
                        $storedFile->path,
                        $downloadName ?: ($storedFile->original_name ?: basename($storedFile->path))
                    )
                    : Storage::disk($storedFile->disk)->response(
                        $storedFile->path,
                        $downloadName ?: ($storedFile->original_name ?: basename($storedFile->path)),
                        ['Content-Disposition' => 'inline; filename="' . addslashes($downloadName ?: ($storedFile->original_name ?: basename($storedFile->path))) . '"']
                    ),
                'result' => $download ? 'legacy_success' : 'legacy_preview_success',
            ];
        }

        $profile = $storedFile->storageProfile;
        if (!$profile instanceof StorageProfile) {
            $storedFile->forceFill(['availability_status' => 'unreachable'])->save();

            return [
                'response' => $this->responseFactory->make('File temporarily unavailable.', 503),
                'result' => 'missing_profile',
            ];
        }

        try {
            $storage = $this->filesystems->build($profile, $this->snapshotFor($storedFile));

            if (!$storage->exists($storedFile->path)) {
                $storedFile->forceFill(['availability_status' => 'deleted'])->save();

                return [
                    'response' => $this->responseFactory->make('File is no longer available.', 404),
                    'result' => 'missing_object',
                ];
            }

            $storedFile->forceFill(['availability_status' => 'available'])->save();

            return [
                'response' => $download
                    ? $storage->download(
                        $storedFile->path,
                        $downloadName ?: ($storedFile->original_name ?: basename($storedFile->path))
                    )
                    : $storage->response(
                        $storedFile->path,
                        $downloadName ?: ($storedFile->original_name ?: basename($storedFile->path)),
                        ['Content-Disposition' => 'inline; filename="' . addslashes($downloadName ?: ($storedFile->original_name ?: basename($storedFile->path))) . '"']
                    ),
                'result' => $profile->is_active ? 'success' : 'success_inactive_profile',
            ];
        } catch (\Throwable $exception) {
            $profile->markReadFailure($exception);
            $storedFile->forceFill(['availability_status' => 'unreachable'])->save();

            return [
                'response' => $this->responseFactory->make('File temporarily unavailable.', 503),
                'result' => 'unreachable',
            ];
        }
    }

    public function publicUrlFromPath(?string $path, ?string $legacyDisk = null): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $storedFile = StoredFile::query()
            ->when(TenantContext::currentId(), fn ($query, $tenantId) => $query->where('tenant_id', $tenantId))
            ->when($legacyDisk !== null && $legacyDisk !== '' && $legacyDisk !== 'public', fn ($query) => $query->where('disk', $legacyDisk))
            ->where('path', $path)
            ->where('visibility', 'public')
            ->latest('id')
            ->first();

        if (!$storedFile) {
            return $this->legacyPublicUrl($legacyDisk ?: (string) config('workspace-files.public_disk', 'public'), $path);
        }

        if (!$storedFile->storage_profile_id || !$storedFile->storageProfile) {
            return $this->legacyPublicUrl($storedFile->disk ?: $legacyDisk ?: (string) config('workspace-files.public_disk', 'public'), $path);
        }

        try {
            $storage = $this->filesystems->build($storedFile->storageProfile, $this->snapshotFor($storedFile));
            $url = $storage->url($path);

            if (preg_match('/^https?:\/\//i', (string) $url)) {
                return $url;
            }

            return url((string) $url);
        } catch (\Throwable $exception) {
            $storedFile->storageProfile->markReadFailure($exception);
            $storedFile->forceFill(['availability_status' => 'unreachable'])->save();

            return $this->legacyPublicUrl($legacyDisk ?: (string) config('workspace-files.public_disk', 'public'), $path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFor(StoredFile $storedFile): array
    {
        return array_filter([
            'storage_driver' => $storedFile->storage_driver,
            'storage_bucket' => $storedFile->storage_bucket,
            'storage_region' => $storedFile->storage_region,
            'storage_endpoint' => $storedFile->storage_endpoint,
            'storage_url' => $storedFile->storage_url,
            'storage_root' => $storedFile->storage_root,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function legacyPublicUrl(string $disk, string $path): ?string
    {
        try {
            $url = Storage::disk($disk)->url($path);

            if (preg_match('/^https?:\/\//i', (string) $url)) {
                return $url;
            }

            return url((string) $url);
        } catch (\Throwable) {
            return null;
        }
    }
}
