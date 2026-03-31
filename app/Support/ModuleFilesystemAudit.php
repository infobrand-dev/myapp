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

        if (!File::exists($base . '/module.json')) {
            $issues[] = 'module.json missing';
        }

        if (!ModulePath::hasMigrationFiles($base)) {
            $issues[] = 'module migrations missing or empty';
        }

        if (!File::isDirectory($base . '/resources/views')) {
            $issues[] = 'resources/views missing';
        }

        if (!File::exists($base . '/routes/web.php')) {
            $issues[] = 'routes/web.php missing';
        }

        return $issues;
    }
}
