<?php

namespace App\Services;

use App\Models\StoredFile;

interface FileMediaProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function process(StoredFile $storedFile): array;
}
