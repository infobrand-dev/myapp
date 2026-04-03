<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WorkspaceMediaStorageService
{
    /**
     * @return array{disk:string,path:string,url:string,content_hash:string,size_bytes:int,deduplicated:bool}
     */
    public function storeUploadedFile(UploadedFile $file, string $directory, string $disk = 'public'): array
    {
        $hash = hash_file('sha256', $file->getRealPath());
        $extension = $this->normalizeExtension($file);
        $path = $this->buildPath($directory, $hash, $extension);
        $storage = Storage::disk($disk);
        $deduplicated = $storage->exists($path);

        if (!$deduplicated) {
            $stream = fopen($file->getRealPath(), 'rb');

            try {
                $storage->put($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'url' => url($storage->url($path)),
            'content_hash' => $hash,
            'size_bytes' => max(0, (int) ($file->getSize() ?? 0)),
            'deduplicated' => $deduplicated,
        ];
    }

    private function buildPath(string $directory, string $hash, string $extension): string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');

        return implode('/', array_filter([
            $directory,
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
}
