<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class CoreModuleBoundaryAudit
{
    public function __construct(
        private readonly PlatformCoreBoundaryRegistry $registry
    ) {
    }

    /**
     * @return array{findings: array<int, array<string, string>>, module_tables: array<string, string>}
     */
    public function run(): array
    {
        $moduleTables = $this->discoverModuleTables();

        $findings = array_merge(
            $this->scanCorePhpFilesForModuleReferences(),
            $this->scanCoreMigrationsForModuleOwnedTables($moduleTables)
        );

        usort($findings, function (array $left, array $right): int {
            return [$left['file'], (int) $left['line'], $left['type']]
                <=> [$right['file'], (int) $right['line'], $right['type']];
        });

        return [
            'findings' => $findings,
            'module_tables' => $moduleTables,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function discoverModuleTables(): array
    {
        $tables = [];

        foreach (File::directories(app_path('Modules')) as $moduleDir) {
            $manifestPath = $moduleDir . DIRECTORY_SEPARATOR . 'module.json';

            if (!File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) File::get($manifestPath), true);
            if (!is_array($manifest) || empty($manifest['slug'])) {
                continue;
            }

            $migrationDir = ModulePath::migrationDirectory($moduleDir);
            if ($migrationDir === null || !File::isDirectory($migrationDir)) {
                continue;
            }

            foreach (File::files($migrationDir) as $file) {
                if (!str_ends_with(strtolower($file->getFilename()), '.php')) {
                    continue;
                }

                foreach ($this->schemaTableReferences((string) File::get($file->getPathname())) as $table) {
                    $tables[$table] ??= (string) $manifest['slug'];
                }
            }
        }

        ksort($tables);

        return $tables;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function scanCorePhpFilesForModuleReferences(): array
    {
        $findings = [];
        $files = array_merge(
            $this->phpFilesIn(app_path(), [app_path('Modules')]),
            $this->phpFilesIn(base_path('routes'))
        );

        foreach ($files as $file) {
            $lines = preg_split('/\R/', (string) File::get($file)) ?: [];

            foreach ($lines as $index => $line) {
                if (!str_contains($line, 'App\\Modules\\')) {
                    continue;
                }

                $relativePath = $this->relativePath($file);
                $approved = $this->registry->isApprovedReference($relativePath, trim($line));

                $findings[] = [
                    'type' => $approved ? 'approved_core_module_reference' : 'core_depends_on_module_class',
                    'file' => $relativePath,
                    'line' => (string) ($index + 1),
                    'detail' => trim($line),
                ];
            }
        }

        return $findings;
    }

    /**
     * @param array<string, string> $moduleTables
     * @return array<int, array<string, string>>
     */
    private function scanCoreMigrationsForModuleOwnedTables(array $moduleTables): array
    {
        $findings = [];

        foreach ($this->phpFilesIn(base_path('database/migrations')) as $file) {
            $lines = preg_split('/\R/', (string) File::get($file)) ?: [];

            foreach ($lines as $index => $line) {
                if (!preg_match("/Schema::(?:create|table|dropIfExists)\\('([^']+)'/", $line, $matches)) {
                    continue;
                }

                $table = $matches[1];
                $owner = $moduleTables[$table] ?? null;
                if ($owner === null) {
                    continue;
                }

                $relativePath = $this->relativePath($file);
                $approved = $this->registry->isApprovedTableTouch($relativePath, $table);

                $findings[] = [
                    'type' => $approved ? 'approved_core_module_migration_touch' : 'core_migration_touches_module_table',
                    'file' => $relativePath,
                    'line' => (string) ($index + 1),
                    'detail' => $table . ' owned by module ' . $owner,
                ];
            }
        }

        return $findings;
    }

    /**
     * @return array<int, string>
     */
    private function schemaTableReferences(string $contents): array
    {
        preg_match_all("/Schema::(?:create|table|dropIfExists)\\('([^']+)'/", $contents, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @param array<int, string> $excludeRoots
     * @return array<int, string>
     */
    private function phpFilesIn(string $root, array $excludeRoots = []): array
    {
        if (!File::isDirectory($root)) {
            return [];
        }

        $files = [];

        foreach (File::allFiles($root) as $file) {
            $path = $file->getPathname();
            if (!str_ends_with(strtolower($path), '.php')) {
                continue;
            }

            $normalizedPath = str_replace('\\', '/', $path);
            $isExcluded = false;

            foreach ($excludeRoots as $excludeRoot) {
                $normalizedExcludeRoot = rtrim(str_replace('\\', '/', $excludeRoot), '/');
                if (str_starts_with($normalizedPath, $normalizedExcludeRoot . '/')) {
                    $isExcluded = true;
                    break;
                }
            }

            if ($isExcluded) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);

        return $files;
    }

    private function relativePath(string $path): string
    {
        return str_replace('\\', '/', ltrim(str_replace(base_path(), '', $path), '\\/'));
    }
}
