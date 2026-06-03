<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class WorkspaceMediaStorageService
{
    public function __construct(
        private readonly StoredFileService $storedFiles,
        private readonly StorageAccessService $access
    ) {
    }

    /**
     * @return array{disk:string,path:string,url:string,content_hash:string,size_bytes:int,deduplicated:bool,stored_file_id:int}
     */
    public function storeUploadedFile(UploadedFile $file, string $directory, ?string $disk = null): array
    {
        $storedFile = $this->storedFiles->storeUploadedFile($file, 'public_asset', [
            'directory' => $directory,
            'disk' => $disk,
            'visibility' => 'public',
            'source_module' => 'core',
            'source_context' => $directory,
        ]);

        return [
            'disk' => $storedFile->disk,
            'path' => $storedFile->path,
            'url' => $this->access->publicUrlFromPath($storedFile->path, $disk) ?? '',
            'content_hash' => (string) $storedFile->content_hash,
            'size_bytes' => (int) $storedFile->size_bytes,
            'deduplicated' => (bool) data_get($storedFile->meta, 'deduplicated', false),
            'stored_file_id' => (int) $storedFile->id,
        ];
    }
}
