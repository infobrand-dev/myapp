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
        $paths = $this->referencedFiles($tenantId);

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

    /**
     * @return array<int, array{disk:string, path:string}>
     */
    public function referencedFiles(?int $tenantId = null): array
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

        foreach ($this->conversationMediaReferences($tenantId) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = $reference;
        }

        foreach ($this->liveChatWidgetLogoReferences($tenantId) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = $reference;
        }

        return array_values($paths);
    }

    /**
     * @return array<int, string>
     */
    public function publicReferencedPaths(?int $tenantId = null): array
    {
        return collect($this->referencedFiles($tenantId))
            ->filter(static fn ($reference) => (string) ($reference['disk'] ?? '') === 'public')
            ->map(static fn ($reference) => ltrim(str_replace('\\', '/', (string) ($reference['path'] ?? '')), '/'))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function publicReferencedPathsForAllTenants(): array
    {
        $paths = [];

        foreach ($this->userAvatarReferences(null) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = ltrim(str_replace('\\', '/', (string) $reference['path']), '/');
        }

        foreach ($this->productMediaReferences(null) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = ltrim(str_replace('\\', '/', (string) $reference['path']), '/');
        }

        foreach ($this->waTemplateMediaReferences(null) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = ltrim(str_replace('\\', '/', (string) $reference['path']), '/');
        }

        foreach ($this->conversationMediaReferences(null) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = ltrim(str_replace('\\', '/', (string) $reference['path']), '/');
        }

        foreach ($this->liveChatWidgetLogoReferences(null) as $reference) {
            $paths[$this->referenceKey($reference['disk'], $reference['path'])] = ltrim(str_replace('\\', '/', (string) $reference['path']), '/');
        }

        return array_values(array_filter($paths));
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
    private function userAvatarReferences(?int $tenantId): array
    {
        if (!Schema::hasTable('users')) {
            return [];
        }

        return DB::table('users')
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->pluck('avatar')
            ->map(fn ($path) => ['disk' => 'public', 'path' => (string) $path])
            ->all();
    }

    /**
     * @return array<int, array{disk:string, path:string}>
     */
    private function productMediaReferences(?int $tenantId): array
    {
        if (!Schema::hasTable('product_media')) {
            return [];
        }

        return DB::table('product_media')
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
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
    private function waTemplateMediaReferences(?int $tenantId): array
    {
        if (!Schema::hasTable('wa_templates')) {
            return [];
        }

        $references = [];

        DB::table('wa_templates')
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
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

    /**
     * @return array<int, array{disk:string, path:string}>
     */
    private function conversationMediaReferences(?int $tenantId): array
    {
        if (!Schema::hasTable('conversation_messages')) {
            return [];
        }

        return DB::table('conversation_messages')
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereNotNull('media_url')
            ->where('media_url', '!=', '')
            ->pluck('media_url')
            ->map(function ($url) {
                $path = $this->publicDiskPathFromUrl((string) $url);

                return $path === null ? null : ['disk' => 'public', 'path' => $path];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{disk:string, path:string}>
     */
    private function liveChatWidgetLogoReferences(?int $tenantId): array
    {
        if (!Schema::hasTable('live_chat_widgets')) {
            return [];
        }

        return DB::table('live_chat_widgets')
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereNotNull('logo_url')
            ->where('logo_url', '!=', '')
            ->pluck('logo_url')
            ->map(function ($url) {
                $path = $this->publicDiskPathFromUrl((string) $url);

                return $path === null ? null : ['disk' => 'public', 'path' => $path];
            })
            ->filter()
            ->values()
            ->all();
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

    private function publicDiskPathFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return null;
        }

        $publicPrefix = parse_url((string) Storage::disk('public')->url(''), PHP_URL_PATH) ?: '/storage';
        $publicPrefix = '/' . trim((string) $publicPrefix, '/');

        if (!str_starts_with($path, $publicPrefix . '/')) {
            return null;
        }

        return $this->normalizePath(substr($path, strlen($publicPrefix) + 1));
    }
}
