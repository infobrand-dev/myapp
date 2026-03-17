<?php

namespace App\Support;

use App\Models\Module;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ModuleManager
{
    public function all(): array
    {
        $manifests = $this->discover();
        $states = $this->statesBySlug();

        $modules = [];
        foreach ($manifests as $slug => $manifest) {
            $state = $states[$slug] ?? null;
            $requires = $manifest['requires'] ?? [];
            $navigation = $this->normalizeNavigation((array) ($manifest['navigation'] ?? []));
            $modules[$slug] = [
                'slug' => $slug,
                'name' => $manifest['name'] ?? $slug,
                'description' => $manifest['description'] ?? '',
                'provider' => $manifest['provider'] ?? null,
                'version' => $manifest['version'] ?? null,
                'requires' => $requires,
                'navigation' => $navigation,
                'installed' => $state ? ($state->installed_at !== null) : false,
                'active' => $state ? (bool) $state->is_active : false,
                'installed_at' => $state?->installed_at,
                'dependents' => $this->dependentsOf($slug, $manifests),
            ];
        }

        ksort($modules);
        return $modules;
    }

    public function isActive(string $slug): bool
    {
        $all = $this->all();
        return isset($all[$slug]) && $all[$slug]['installed'] && $all[$slug]['active'];
    }

    public function activeProviders(): array
    {
        $providers = [];
        foreach ($this->all() as $module) {
            if (!$module['installed'] || !$module['active']) {
                continue;
            }

            if (!empty($module['provider']) && class_exists($module['provider'])) {
                $providers[] = $module['provider'];
            }
        }

        return $providers;
    }

    public function install(string $slug): void
    {
        $all = $this->all();
        if (!isset($all[$slug])) {
            throw new RuntimeException("Module '{$slug}' tidak ditemukan.");
        }

        $module = $all[$slug];
        foreach ($module['requires'] as $requiredSlug) {
            if (empty($all[$requiredSlug]) || !$all[$requiredSlug]['installed']) {
                throw new RuntimeException("Module '{$slug}' membutuhkan module '{$requiredSlug}' sudah ter-install.");
            }
        }

        $migrationPath = base_path('app/Modules/' . $this->manifestDirName($slug) . '/database/migrations');
        if (File::isDirectory($migrationPath)) {
            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $migrationPath);
            $this->callArtisanOrFail('migrate', ['--path' => $relativePath, '--force' => true]);
        }

        if (class_exists(\Database\Seeders\RoleSeeder::class)) {
            $this->callArtisanOrFail('db:seed', [
                '--class' => \Database\Seeders\RoleSeeder::class,
                '--force' => true,
            ]);
        }

        $record = Module::query()->firstOrNew(['slug' => $slug]);
        $record->fill([
            'name' => $module['name'],
            'provider' => $module['provider'],
            'version' => $module['version'],
        ]);
        if ($record->installed_at === null) {
            $record->installed_at = now();
        }
        if ($record->is_active === null) {
            $record->is_active = false;
        }
        $record->saveOrFail();
    }

    public function activate(string $slug): void
    {
        $all = $this->all();
        if (empty($all[$slug])) {
            throw new RuntimeException("Module '{$slug}' tidak ditemukan.");
        }
        if (!$all[$slug]['installed']) {
            throw new RuntimeException("Module '{$slug}' belum di-install.");
        }
        foreach ($all[$slug]['requires'] as $requiredSlug) {
            if (empty($all[$requiredSlug]) || !$all[$requiredSlug]['installed']) {
                throw new RuntimeException("Module '{$slug}' membutuhkan module '{$requiredSlug}' ter-install.");
            }
            if (!$all[$requiredSlug]['active']) {
                throw new RuntimeException("Module '{$slug}' membutuhkan module '{$requiredSlug}' aktif.");
            }
        }

        $record = Module::query()->where('slug', $slug)->firstOrFail();
        $record->is_active = true;
        $record->saveOrFail();
    }

    public function deactivate(string $slug): void
    {
        $all = $this->all();
        if (empty($all[$slug]) || !$all[$slug]['installed']) {
            throw new RuntimeException("Module '{$slug}' belum di-install.");
        }

        $activeDependents = [];
        foreach ($all as $module) {
            if ($module['active'] && in_array($slug, $module['requires'], true)) {
                $activeDependents[] = $module['slug'];
            }
        }
        if (!empty($activeDependents)) {
            throw new RuntimeException(
                "Module '{$slug}' tidak bisa dinonaktifkan karena masih dipakai: " . implode(', ', $activeDependents)
            );
        }

        $record = Module::query()->where('slug', $slug)->firstOrFail();
        $record->is_active = false;
        $record->saveOrFail();
    }

    private function discover(): array
    {
        $root = app_path('Modules');
        if (!File::isDirectory($root)) {
            return [];
        }

        $data = [];
        foreach (File::directories($root) as $moduleDir) {
            $manifestPath = $moduleDir . DIRECTORY_SEPARATOR . 'module.json';
            if (!File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) File::get($manifestPath), true);
            if (!is_array($manifest) || empty($manifest['slug'])) {
                continue;
            }

            $slug = (string) $manifest['slug'];
            $manifest['_dir'] = basename($moduleDir);
            $manifest['requires'] = array_values(array_filter((array) ($manifest['requires'] ?? [])));
            $data[$slug] = $manifest;
        }

        return $data;
    }

    private function statesBySlug(): array
    {
        if (!$this->moduleTableReady()) {
            return [];
        }

        return Module::query()->get()->keyBy('slug')->all();
    }

    private function moduleTableReady(): bool
    {
        try {
            return Schema::hasTable('modules');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function dependentsOf(string $slug, array $manifests): array
    {
        $dependents = [];
        foreach ($manifests as $manifest) {
            $requires = (array) ($manifest['requires'] ?? []);
            if (in_array($slug, $requires, true)) {
                $dependents[] = $manifest['slug'] ?? null;
            }
        }

        return array_values(array_filter($dependents));
    }

    private function manifestDirName(string $slug): string
    {
        $manifest = $this->discover()[$slug] ?? null;
        if (!$manifest) {
            throw new RuntimeException("Manifest module '{$slug}' tidak ditemukan.");
        }

        return (string) ($manifest['_dir'] ?? '');
    }

    private function callArtisanOrFail(string $command, array $parameters = []): void
    {
        $exitCode = Artisan::call($command, $parameters);
        if ($exitCode !== 0) {
            $output = trim((string) Artisan::output());
            throw new RuntimeException("Command '{$command}' gagal dijalankan. {$output}");
        }
    }

    private function normalizeNavigation(array $navigation): array
    {
        $normalized = [];
        foreach ($navigation as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (empty($item['route']) || empty($item['label'])) {
                continue;
            }

            $normalized[] = [
                'label' => (string) $item['label'],
                'route' => (string) $item['route'],
                'role' => isset($item['role']) ? (string) $item['role'] : null,
                'badge' => isset($item['badge']) ? (string) $item['badge'] : null,
            ];
        }

        return $normalized;
    }
}
