<?php

namespace App\Services;

use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TenantStorageUsageService
{
    public function usedBytes(?int $tenantId = null): int
    {
        $tenantId ??= TenantContext::currentId();
        $paths = [];

        foreach ($this->userAvatarReferences($tenantId) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = $reference;
        }

        foreach ($this->productMediaReferences($tenantId) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = $reference;
        }

        foreach ($this->waTemplateMediaReferences($tenantId) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = $reference;
        }

        $total = 0;

        foreach ($paths as $reference) {
            $total += $this->fileSize((string) $reference['disk'], (string) $reference['path']);
        }

        return $total;
    }

    public function ensureCanStoreUpload(
        UploadedFile $file,
        ?int $tenantId = null,
        ?string $message = null,
        int $releasedBytes = 0
    ): void {
        $this->ensureCanStoreBytes((int) ($file->getSize() ?? 0), $tenantId, $message, $releasedBytes);
    }

    public function ensureCanStoreBytes(
        int $incomingBytes,
        ?int $tenantId = null,
        ?string $message = null,
        int $releasedBytes = 0
    ): void {
        $tenantId ??= TenantContext::currentId();
        $incomingBytes = max(0, $incomingBytes);
        $releasedBytes = max(0, $releasedBytes);

        $limit = app(TenantPlanManager::class)->limit(PlanLimit::TOTAL_STORAGE_BYTES, $tenantId);
        if ($limit === null) {
            return;
        }

        $usage = $this->usedBytes($tenantId);
        $projected = max($usage - $releasedBytes, 0) + $incomingBytes;

        if ($projected <= $limit) {
            return;
        }

        throw ValidationException::withMessages([
            'plan' => $message ?: app(TenantPlanManager::class)->defaultLimitMessageFor(PlanLimit::TOTAL_STORAGE_BYTES, $tenantId),
        ]);
    }

    public function fileSize(string $disk, ?string $path): int
    {
        $path = $this->normalizePath($path);
        if ($path === null) {
            return 0;
        }

        try {
            $storage = Storage::disk($disk);

            if (!$storage->exists($path)) {
                return 0;
            }

            return max(0, (int) $storage->size($path));
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array<int, array{disk:string, path:string}>
     */
    private function userAvatarReferences(int $tenantId): array
    {
        if (!Schema::hasTable('users')) {
            return [];
        }

        return DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->pluck('avatar')
            ->map(fn ($path) => ['disk' => 'public', 'path' => (string) $path])
            ->all();
    }

    /**
     * @return array<int, array{disk:string, path:string}>
     */
    private function productMediaReferences(int $tenantId): array
    {
        if (!Schema::hasTable('product_media')) {
            return [];
        }

        return DB::table('product_media')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('disk')
            ->where('disk', '!=', '')
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->get(['disk', 'path'])
            ->map(fn ($row) => ['disk' => (string) $row->disk, 'path' => (string) $row->path])
            ->all();
    }

    /**
     * @return array<int, array{disk:string, path:string}>
     */
    private function waTemplateMediaReferences(int $tenantId): array
    {
        if (!Schema::hasTable('wa_templates')) {
            return [];
        }

        $references = [];

        DB::table('wa_templates')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('components')
            ->orderBy('id')
            ->select(['id', 'components'])
            ->chunkById(100, function ($rows) use (&$references) {
                foreach ($rows as $row) {
                    $components = json_decode((string) $row->components, true);
                    if (!is_array($components)) {
                        continue;
                    }

                    foreach ($components as $component) {
                        $parameters = is_array($component['parameters'] ?? null) ? $component['parameters'] : [];
                        foreach ($parameters as $parameter) {
                            $disk = $parameter['storage_disk'] ?? null;
                            $path = $parameter['storage_path'] ?? null;

                            if (!is_string($disk) || $disk === '' || !is_string($path) || trim($path) === '') {
                                continue;
                            }

                            $references[] = [
                                'disk' => $disk,
                                'path' => $path,
                            ];
                        }
                    }
                }
            });

        return $references;
    }

    private function referenceKey(string $disk, string $path): string
    {
        return $disk . '::' . ltrim(str_replace('\\', '/', $path), '/');
    }

    private function normalizePath(?string $path): ?string
    {
        if (!is_string($path)) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        return $path === '' ? null : $path;
    }
}
