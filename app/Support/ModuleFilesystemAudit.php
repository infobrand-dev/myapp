<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class ModuleFilesystemAudit
{
    public function __construct(
        private readonly ModuleManager $modules
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeInstalledIssues(): array
    {
        $issues = [];

        foreach ($this->modules->all() as $module) {
            if (empty($module['installed']) || empty($module['active'])) {
                continue;
            }

            $moduleIssues = $this->issuesForModule($module);

            if (!empty($moduleIssues)) {
                $issues[] = [
                    'slug' => (string) ($module['slug'] ?? ''),
                    'name' => (string) ($module['name'] ?? ($module['slug'] ?? '')),
                    'issues' => $moduleIssues,
                ];
            }
        }

        return $issues;
    }

    /**
     * @param array<string, mixed> $module
     * @return array<int, string>
     */
    public function issuesForModule(array $module): array
    {
        $dir = (string) ($module['_dir'] ?? '');

        if ($dir === '') {
            return ['module directory metadata missing'];
        }

        $base = base_path('app/Modules/' . $dir);
        $issues = [];

        if (!File::isDirectory($base)) {
            $issues[] = 'module directory missing: app/Modules/' . $dir;
            return $issues;
        }

        if (!$this->hasExactCasePath(base_path('app/Modules'), $dir)) {
            $issues[] = 'module directory casing mismatch: app/Modules/' . $dir;
        }

        if (!File::exists($base . '/module.json')) {
            $issues[] = 'module.json missing';
        } elseif (!$this->hasExactCasePath($base, 'module.json')) {
            $issues[] = 'module.json casing mismatch';
        }

        $migrationDir = ModulePath::migrationDirectory($base);
        if ($migrationDir === null || !ModulePath::hasMigrationFiles($base)) {
            $issues[] = 'module migrations missing or empty';
        } else {
            $expectedMigrationDir = str_starts_with(str_replace('\\', '/', $migrationDir), str_replace('\\', '/', $base . '/Database/Migrations'))
                ? 'Database/Migrations'
                : 'database/migrations';

            if (!$this->hasExactNestedPath($base, $expectedMigrationDir)) {
                $issues[] = 'module migration directory casing mismatch: ' . $expectedMigrationDir;
            }
        }

        if (!File::isDirectory($base . '/resources/views')) {
            $issues[] = 'resources/views missing';
        } elseif (!$this->hasExactNestedPath($base, 'resources/views')) {
            $issues[] = 'resources/views casing mismatch';
        }

        if (!File::exists($base . '/routes/web.php')) {
            $issues[] = 'routes/web.php missing';
        } elseif (!$this->hasExactNestedPath($base, 'routes/web.php')) {
            $issues[] = 'routes/web.php casing mismatch';
        }

        return $issues;
    }

    private function hasExactNestedPath(string $base, string $relativePath): bool
    {
        $current = rtrim($base, DIRECTORY_SEPARATOR);
        $segments = array_values(array_filter(preg_split('/[\\\\\/]+/', $relativePath) ?: []));

        foreach ($segments as $segment) {
            if (!$this->hasExactCasePath($current, $segment)) {
                return false;
            }

            $current .= DIRECTORY_SEPARATOR . $segment;
        }

        return true;
    }

    private function hasExactCasePath(string $parent, string $expectedName): bool
    {
        if (!File::isDirectory($parent)) {
            return false;
        }

        foreach (File::glob($parent . DIRECTORY_SEPARATOR . '*') as $entry) {
            if (basename($entry) === $expectedName) {
                return true;
            }
        }

        return false;
    }
}
