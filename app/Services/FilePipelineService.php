<?php

namespace App\Services;

use App\Models\StoredFile;

class FilePipelineService
{
    public function __construct(
        private readonly FileMalwareScanner $scanner,
        private readonly FileMediaProcessor $processor
    ) {
    }

    public function runPostUpload(StoredFile $storedFile): void
    {
        $meta = is_array($storedFile->meta) ? $storedFile->meta : [];
        $meta['pipeline'] = [
            'scan' => $this->scanner->scan($storedFile),
            'process' => $this->processor->process($storedFile),
        ];

        $storedFile->forceFill(['meta' => $meta])->save();
    }
}
