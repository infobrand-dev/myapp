<?php

namespace App\Support;

use App\Models\Module;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ModuleManager
{
    private ?array $allModulesCache = null;
    private ?array $manifestCache = null;
    private ?array $statesBySlugCache = null;
    private ?array $ranMigrationsCache = null;
    private ?bool $moduleTableReadyCache = null;
    private ?bool $migrationsTableReadyCache = null;

    public function all(): array
    {
        if (!app()->environment('testing') && $this->allModulesCache !== null) {
            return $this->allModulesCache;
        }

        $manifests = $this->discover();
        $states = $this->statesBySlug();

        $modules = [];
        foreach ($manifests as $slug => $manifest) {
            $state = $states[$slug] ?? null;
            $requires = $manifest['requires'] ?? [];
            $navigation = $this->normalizeNavigation((array) ($manifest['navigation'] ?? []));
            $pendingMigrationFiles = $state && $state->installed_at !== null
                ? $this->pendingMigrationFilesForSlug($slug, $manifest)
                : [];
            $pendingMigrations = array_column($pendingMigrationFiles, 'name');
            $meta = is_array($state?->meta) ? $state->meta : [];
            $modules[$slug] = [
                'slug' => $slug,
                '_dir' => $manifest['_dir'] ?? null,
                'name' => $manifest['name'] ?? $slug,
                'category' => $manifest['category'] ?? 'uncategorized',
                'description' => $manifest['description'] ?? '',
                'provider' => $manifest['provider'] ?? null,
                'version' => $manifest['version'] ?? null,
                'icon' => $manifest['icon'] ?? null,
                'requires' => $requires,
                'navigation' => $navigation,
                'installed' => $state ? ($state->installed_at !== null) : false,
                'active' => $state ? (bool) $state->is_active : false,
                'installed_at' => $state ? $state->installed_at : null,
                'pending_migrations' => $pendingMigrations,
                'pending_migration_files' => $pendingMigrationFiles,
                'has_pending_db_update' => !empty($pendingMigrations),
                'last_db_update_status' => Arr::get($meta, 'db_update.status'),
                'last_db_update_at' => Arr::get($meta, 'db_update.finished_at'),
                'last_db_update_error' => Arr::get($meta, 'db_update.error'),
                'dependents' => $this->dependentsOf($slug, $manifests),
            ];
        }

        ksort($modules);

        if (!app()->environment('testing')) {
            $this->allModulesCache = $modules;
        }

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
        $this->forgetRuntimeCaches();
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

        $migrationPath = ModulePath::migrationDirectory(
            base_path('app/Modules/' . $this->manifestDirName($slug))
        );
        if ($migrationPath !== null) {
            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $migrationPath);
            $this->callArtisanOrFail('migrate', ['--path' => $relativePath, '--force' => true]);
        }

        if (class_exists(\Database\Seeders\RoleSeeder::class)) {
            $this->callArtisanOrFail('db:seed', [
                '--class' => \Database\Seeders\RoleSeeder::class,
                '--force' => true,
            ]);
        }

        $record = Module::query()->where('slug', $slug)->first();
        $payload = [
            'slug' => $slug,
            'name' => $module['name'],
            'provider' => $module['provider'],
            'version' => $module['version'],
        ];

        if (!$record || $record->installed_at === null) {
            $payload['installed_at'] = now();
        }

        if (!$record) {
            $payload['is_active'] = false;
            $this->insertModuleRow($payload);
        } else {
            $this->updateModuleRow($slug, Arr::except($payload, ['slug']));
        }

        $this->forgetRuntimeCaches();
    }

    public function activate(string $slug): void
    {
        $this->forgetRuntimeCaches();
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

        $migrationPath = ModulePath::migrationDirectory(
            base_path('app/Modules/' . $this->manifestDirName($slug))
        );
        if ($migrationPath !== null) {
            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $migrationPath);
            $this->callArtisanOrFail('migrate', ['--path' => $relativePath, '--force' => true]);
        }

        Module::query()->where('slug', $slug)->firstOrFail();
        $this->updateModuleRow($slug, ['is_active' => true]);

        if (!empty($all[$slug]['provider']) && class_exists($all[$slug]['provider'])) {
            app()->register($all[$slug]['provider']);
        }

        app(TenantRoleProvisioner::class)->ensureForAllTenants();
        $this->forgetRuntimeCaches();
    }

    public function deactivate(string $slug): void
    {
        $this->forgetRuntimeCaches();
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

        Module::query()->where('slug', $slug)->firstOrFail();
        $this->updateModuleRow($slug, ['is_active' => false]);
        $this->forgetRuntimeCaches();
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array{requested: array<int, string>, expanded: array<int, string>, executed: array<int, string>, action: string}
     */
    public function runBulkAction(string $action, array $slugs, ?int $ranBy = null): array
    {
        $requested = array_values(array_unique(array_filter(array_map(
            fn (mixed $slug): string => trim((string) $slug),
            $slugs
        ))));

        if ($requested === []) {
            throw new RuntimeException('Pilih minimal satu module terlebih dahulu.');
        }

        return match ($action) {
            'install' => $this->bulkInstall($requested),
            'activate' => $this->bulkActivate($requested),
            'deactivate' => $this->bulkDeactivate($requested),
            'db-update' => $this->bulkRunPendingDbUpdate($requested, $ranBy),
            default => throw new RuntimeException("Bulk action '{$action}' tidak dikenali."),
        };
    }

    public function runPendingDbUpdate(string $slug, ?int $ranBy = null): int
    {
        $this->forgetRuntimeCaches();
        $all = $this->all();
        if (empty($all[$slug])) {
            throw new RuntimeException("Module '{$slug}' tidak ditemukan.");
        }

        if (!$all[$slug]['installed']) {
            throw new RuntimeException("Module '{$slug}' belum di-install.");
        }

        $lock = Cache::lock('module-db-update:' . $slug, 120);
        if (!$lock->get()) {
            throw new RuntimeException("DB update untuk module '{$slug}' sedang berjalan.");
        }

        try {
            $pendingMigrations = $this->pendingMigrationNamesForSlug($slug);
            if ($pendingMigrations === []) {
                $this->recordDbUpdateStatus($slug, 'noop', null, $ranBy);
                return 0;
            }

            $this->recordDbUpdateStatus($slug, 'running', null, $ranBy);

            $migrationPath = ModulePath::migrationDirectory(
                base_path('app/Modules/' . $this->manifestDirName($slug))
            );

            if ($migrationPath === null) {
                throw new RuntimeException("Module '{$slug}' tidak memiliki folder migration.");
            }

            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $migrationPath);
            $this->callArtisanOrFail('migrate', ['--path' => $relativePath, '--force' => true]);

            $this->recordDbUpdateStatus($slug, 'success', null, $ranBy, count($pendingMigrations));
            $this->forgetRuntimeCaches();

            return count($pendingMigrations);
        } catch (\Throwable $e) {
            $this->recordDbUpdateStatus($slug, 'failed', $e->getMessage(), $ranBy);
            $this->forgetRuntimeCaches();
            throw $e;
        } finally {
            optional($lock)->release();
        }
    }

    public function runSingleMigration(string $slug, string $migrationName, ?int $ranBy = null): void
    {
        $this->forgetRuntimeCaches();
        $all = $this->all();
        if (empty($all[$slug])) {
            throw new RuntimeException("Module '{$slug}' tidak ditemukan.");
        }

        if (!$all[$slug]['installed']) {
            throw new RuntimeException("Module '{$slug}' belum di-install.");
        }

        $lock = Cache::lock('module-db-update:' . $slug, 120);
        if (!$lock->get()) {
            throw new RuntimeException("DB update untuk module '{$slug}' sedang berjalan.");
        }

        try {
            $pendingFiles = collect($this->pendingMigrationFilesForSlug($slug));
            $target = $pendingFiles->firstWhere('name', $migrationName);

            if (!$target) {
                throw new RuntimeException("Migration '{$migrationName}' tidak pending atau tidak ditemukan di module '{$slug}'.");
            }

            $this->recordDbUpdateStatus($slug, 'running', null, $ranBy);
            $this->callArtisanOrFail('migrate', ['--path' => $target['path'], '--force' => true]);
            $this->recordDbUpdateStatus($slug, 'success', null, $ranBy, 1);
            $this->forgetRuntimeCaches();
        } catch (\Throwable $e) {
            $this->recordDbUpdateStatus($slug, 'failed', $e->getMessage(), $ranBy);
            $this->forgetRuntimeCaches();
            throw $e;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * @return array<int, string>
     */
    public function pendingMigrationNamesForSlug(string $slug, ?array $manifest = null): array
    {
        $manifest ??= $this->discover()[$slug] ?? null;
        if (!$manifest) {
            throw new RuntimeException("Manifest module '{$slug}' tidak ditemukan.");
        }

        $migrationPath = ModulePath::migrationDirectory(
            base_path('app/Modules/' . ($manifest['_dir'] ?? $this->manifestDirName($slug)))
        );

        if ($migrationPath === null || !$this->migrationsTableReady()) {
            return [];
        }

        $ranLookup = array_fill_keys(array_map('strtolower', $this->ranMigrations()), true);

        return collect(File::files($migrationPath))
            ->filter(fn ($file) => str_ends_with(strtolower($file->getFilename()), '.php'))
            ->map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME))
            ->filter(fn (string $migration) => !isset($ranLookup[strtolower($migration)]))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name:string,path:string}>
     */
    public function pendingMigrationFilesForSlug(string $slug, ?array $manifest = null): array
    {
        $manifest ??= $this->discover()[$slug] ?? null;
        if (!$manifest) {
            throw new RuntimeException("Manifest module '{$slug}' tidak ditemukan.");
        }

        $migrationPath = ModulePath::migrationDirectory(
            base_path('app/Modules/' . ($manifest['_dir'] ?? $this->manifestDirName($slug)))
        );

        if ($migrationPath === null || !$this->migrationsTableReady()) {
            return [];
        }

        $ranLookup = array_fill_keys(array_map('strtolower', $this->ranMigrations()), true);

        return collect(File::files($migrationPath))
            ->filter(fn ($file) => str_ends_with(strtolower($file->getFilename()), '.php'))
            ->map(function ($file): array {
                $filename = $file->getFilename();

                return [
                    'name' => pathinfo($filename, PATHINFO_FILENAME),
                    'path' => $this->relativeMigrationPath($file->getPathname()),
                ];
            })
            ->filter(fn (array $migration) => !isset($ranLookup[strtolower($migration['name'])]))
            ->values()
            ->all();
    }

    private function discover(): array
    {
        if ($this->manifestCache !== null) {
            return $this->manifestCache;
        }

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
            $manifest['category'] = $this->normalizeCategory($manifest['category'] ?? null);
            $manifest['requires'] = array_values(array_filter((array) ($manifest['requires'] ?? [])));
            $data[$slug] = $manifest;
        }

        return $this->manifestCache = $data;
    }

    private function statesBySlug(): array
    {
        if (!app()->environment('testing') && $this->statesBySlugCache !== null) {
            return $this->statesBySlugCache;
        }

        if (!$this->moduleTableReady()) {
            return [];
        }

        $states = Module::query()->get()->keyBy('slug')->all();

        if (!app()->environment('testing')) {
            $this->statesBySlugCache = $states;
        }

        return $states;
    }

    private function moduleTableReady(): bool
    {
        if (!app()->environment('testing') && $this->moduleTableReadyCache !== null) {
            return $this->moduleTableReadyCache;
        }

        try {
            $ready = Schema::hasTable('modules');

            if (!app()->environment('testing')) {
                $this->moduleTableReadyCache = $ready;
            }

            return $ready;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function migrationsTableReady(): bool
    {
        if (!app()->environment('testing') && $this->migrationsTableReadyCache !== null) {
            return $this->migrationsTableReadyCache;
        }

        try {
            $ready = Schema::hasTable('migrations');

            if (!app()->environment('testing')) {
                $this->migrationsTableReadyCache = $ready;
            }

            return $ready;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    private function ranMigrations(): array
    {
        if ($this->ranMigrationsCache !== null) {
            return $this->ranMigrationsCache;
        }

        return $this->ranMigrationsCache = DB::table('migrations')->pluck('migration')->all();
    }

    private function forgetRuntimeCaches(): void
    {
        $this->allModulesCache = null;
        $this->manifestCache = null;
        $this->statesBySlugCache = null;
        $this->ranMigrationsCache = null;
        $this->moduleTableReadyCache = null;
        $this->migrationsTableReadyCache = null;
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

    private function relativeMigrationPath(string $absolutePath): string
    {
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $absolutePath);

        return str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    }

    private function recordDbUpdateStatus(string $slug, string $status, ?string $error = null, ?int $ranBy = null, ?int $appliedCount = null): void
    {
        if (!$this->moduleTableReady()) {
            return;
        }

        $record = Module::query()->where('slug', $slug)->first();
        if (!$record) {
            return;
        }

        $meta = is_array($record->meta) ? $record->meta : [];
        $meta['db_update'] = array_filter([
            'status' => $status,
            'error' => $error ? mb_substr($error, 0, 65535) : null,
            'ran_by' => $ranBy,
            'applied_count' => $appliedCount,
            'finished_at' => in_array($status, ['success', 'failed', 'noop'], true) ? now()->toDateTimeString() : null,
            'started_at' => $status === 'running'
                ? now()->toDateTimeString()
                : Arr::get($meta, 'db_update.started_at'),
        ], fn ($value) => $value !== null);

        $record->meta = $meta;
        $record->saveOrFail();
    }

    private function insertModuleRow(array $attributes): void
    {
        $now = now();
        $attributes['created_at'] = $attributes['created_at'] ?? $now;
        $attributes['updated_at'] = $attributes['updated_at'] ?? $now;

        $query = DB::table('modules');
        if (array_key_exists('is_active', $attributes)) {
            $query->insert(array_merge(
                Arr::except($attributes, ['is_active']),
                ['is_active' => DB::raw($this->databaseBoolean((bool) $attributes['is_active']))]
            ));

            return;
        }

        $query->insert($attributes);
    }

    private function updateModuleRow(string $slug, array $attributes): void
    {
        $attributes['updated_at'] = $attributes['updated_at'] ?? now();

        if (array_key_exists('is_active', $attributes)) {
            $attributes['is_active'] = DB::raw($this->databaseBoolean((bool) $attributes['is_active']));
        }

        DB::table('modules')
            ->where('slug', $slug)
            ->update($attributes);
    }

    private function databaseBoolean(bool $value): string
    {
        return $value ? 'true' : 'false';
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
                'feature' => isset($item['feature']) ? (string) $item['feature'] : null,
            ];
        }

        return $normalized;
    }

    private function normalizeCategory(mixed $category): string
    {
        if (!is_string($category)) {
            return 'uncategorized';
        }

        $category = trim(strtolower($category));

        return $category !== '' ? $category : 'uncategorized';
    }

    /**
     * @param  array<int, string>  $requested
     * @return array{requested: array<int, string>, expanded: array<int, string>, executed: array<int, string>, action: string}
     */
    private function bulkInstall(array $requested): array
    {
        $expanded = $this->expandWithDependencies($requested);
        $ordered = $this->topologicalOrder($expanded);
        $executed = [];

        foreach ($ordered as $slug) {
            if (($this->all()[$slug]['installed'] ?? false) === true) {
                continue;
            }

            $this->install($slug);
            $executed[] = $slug;
        }

        return [
            'action' => 'install',
            'requested' => $requested,
            'expanded' => $expanded,
            'executed' => $executed,
        ];
    }

    /**
     * @param  array<int, string>  $requested
     * @return array{requested: array<int, string>, expanded: array<int, string>, executed: array<int, string>, action: string}
     */
    private function bulkActivate(array $requested): array
    {
        $expanded = $this->expandWithDependencies($requested);
        $ordered = $this->topologicalOrder($expanded);
        $executed = [];

        foreach ($ordered as $slug) {
            $state = $this->all()[$slug] ?? null;
            if (!$state) {
                throw new RuntimeException("Module '{$slug}' tidak ditemukan.");
            }

            if (!$state['installed']) {
                $this->install($slug);
            }

            if (($this->all()[$slug]['active'] ?? false) === true) {
                continue;
            }

            $this->activate($slug);
            $executed[] = $slug;
        }

        return [
            'action' => 'activate',
            'requested' => $requested,
            'expanded' => $expanded,
            'executed' => $executed,
        ];
    }

    /**
     * @param  array<int, string>  $requested
     * @return array{requested: array<int, string>, expanded: array<int, string>, executed: array<int, string>, action: string}
     */
    private function bulkDeactivate(array $requested): array
    {
        $this->assertNoExternalActiveDependents($requested);

        $ordered = array_reverse($this->topologicalOrder($requested));
        $executed = [];

        foreach ($ordered as $slug) {
            if (($this->all()[$slug]['active'] ?? false) !== true) {
                continue;
            }

            $this->deactivate($slug);
            $executed[] = $slug;
        }

        return [
            'action' => 'deactivate',
            'requested' => $requested,
            'expanded' => $requested,
            'executed' => $executed,
        ];
    }

    /**
     * @param  array<int, string>  $requested
     * @return array{requested: array<int, string>, expanded: array<int, string>, executed: array<int, string>, action: string}
     */
    private function bulkRunPendingDbUpdate(array $requested, ?int $ranBy): array
    {
        $expanded = $this->expandWithDependencies($requested);
        $ordered = $this->topologicalOrder($expanded);
        $executed = [];

        foreach ($ordered as $slug) {
            $state = $this->all()[$slug] ?? null;
            if (!$state || !$state['installed'] || !$state['has_pending_db_update']) {
                continue;
            }

            $this->runPendingDbUpdate($slug, $ranBy);
            $executed[] = $slug;
        }

        return [
            'action' => 'db-update',
            'requested' => $requested,
            'expanded' => $expanded,
            'executed' => $executed,
        ];
    }

    /**
     * @param  array<int, string>  $requested
     * @return array<int, string>
     */
    private function expandWithDependencies(array $requested): array
    {
        $all = $this->all();
        $resolved = [];
        $stack = $requested;

        while ($stack !== []) {
            $slug = array_pop($stack);
            if ($slug === null || isset($resolved[$slug])) {
                continue;
            }

            if (!isset($all[$slug])) {
                throw new RuntimeException("Module '{$slug}' tidak ditemukan.");
            }

            $resolved[$slug] = true;

            foreach ($all[$slug]['requires'] as $requiredSlug) {
                if (!isset($resolved[$requiredSlug])) {
                    $stack[] = $requiredSlug;
                }
            }
        }

        return array_keys($resolved);
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<int, string>
     */
    private function topologicalOrder(array $slugs): array
    {
        $all = $this->all();
        $selected = array_fill_keys($slugs, true);
        $ordered = [];
        $visiting = [];
        $visited = [];

        $visit = function (string $slug) use (&$visit, &$ordered, &$visiting, &$visited, $all, $selected): void {
            if (isset($visited[$slug])) {
                return;
            }

            if (isset($visiting[$slug])) {
                throw new RuntimeException("Circular dependency terdeteksi pada module '{$slug}'.");
            }

            if (!isset($all[$slug])) {
                throw new RuntimeException("Module '{$slug}' tidak ditemukan.");
            }

            $visiting[$slug] = true;

            foreach ($all[$slug]['requires'] as $requiredSlug) {
                if (isset($selected[$requiredSlug])) {
                    $visit($requiredSlug);
                }
            }

            unset($visiting[$slug]);
            $visited[$slug] = true;
            $ordered[] = $slug;
        };

        foreach (array_keys($selected) as $slug) {
            $visit($slug);
        }

        return $ordered;
    }

    /**
     * @param  array<int, string>  $selectedSlugs
     */
    private function assertNoExternalActiveDependents(array $selectedSlugs): void
    {
        $all = $this->all();
        $selected = array_fill_keys($selectedSlugs, true);
        $blockedBy = [];

        foreach ($selectedSlugs as $slug) {
            if (!isset($all[$slug])) {
                throw new RuntimeException("Module '{$slug}' tidak ditemukan.");
            }

            foreach ($all as $module) {
                if (
                    $module['active']
                    && in_array($slug, $module['requires'], true)
                    && !isset($selected[$module['slug']])
                ) {
                    $blockedBy[$slug][] = $module['slug'];
                }
            }
        }

        if ($blockedBy === []) {
            return;
        }

        $messages = collect($blockedBy)
            ->map(fn (array $dependents, string $slug) => $slug . ' dipakai oleh ' . implode(', ', $dependents))
            ->values()
            ->implode('; ');

        throw new RuntimeException('Bulk deactivate diblokir: ' . $messages . '. Pilih module dependent-nya juga atau nonaktifkan satu per satu.');
    }
}
