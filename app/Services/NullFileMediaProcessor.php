<?php

namespace App\Services;

use App\Models\StoredFile;

class NullFileMediaProcessor implements FileMediaProcessor
{
    public function process(StoredFile $storedFile): array
    {
        return [
            'status' => 'not_configured',
            'processed_at' => now()->toIso8601String(),
        ];
    }
}
