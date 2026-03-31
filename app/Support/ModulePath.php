<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class ModulePath
{
    public static function migrationDirectory(string $moduleBase): ?string
    {
        foreach ([
            $moduleBase . '/Database/Migrations',
            $moduleBase . '/database/migrations',
        ] as $path) {
            if (File::isDirectory($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function hasMigrationFiles(string $moduleBase): bool
    {
        $migrationDir = self::migrationDirectory($moduleBase);

        if ($migrationDir === null) {
            return false;
        }

        return collect(File::files($migrationDir))
            ->contains(fn ($file) => str_ends_with(strtolower($file->getFilename()), '.php'));
    }
}
