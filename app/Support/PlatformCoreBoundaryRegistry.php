<?php

namespace App\Support;

class PlatformCoreBoundaryRegistry
{
    /**
     * @return array<string, array<int, string>>
     */
    public function approvedModuleReferences(): array
    {
        /** @var array<string, array<int, string>> $references */
        $references = config('platform-core.boundary.approved_module_references', []);

        return $references;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function approvedModuleTableTouches(): array
    {
        /** @var array<string, array<int, string>> $touches */
        $touches = config('platform-core.boundary.approved_module_table_touches', []);

        return $touches;
    }

    public function isApprovedReference(string $file, string $line): bool
    {
        $approved = $this->approvedModuleReferences()[$file] ?? [];

        foreach ($approved as $pattern) {
            if ($pattern !== '' && str_contains($line, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function isApprovedTableTouch(string $file, string $table): bool
    {
        return in_array($table, $this->approvedModuleTableTouches()[$file] ?? [], true);
    }
}
