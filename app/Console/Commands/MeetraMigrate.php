<?php

namespace App\Console\Commands;

use App\Support\ModuleManager;
use App\Support\ModulePath;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MeetraMigrate extends Command
{
    protected $signature = 'meetra:migrate
                            {--module= : Filter to core or a specific module slug}
                            {--show-all : Show applied migrations too, not just pending}
                            {--command-for= : Print ready-to-run artisan migrate commands for pending item numbers, e.g. 1,3,5}';

    protected $description = 'Scan core and module migration files against the migrations table, then print pending migration commands.';

    public function handle(ModuleManager $moduleManager): int
    {
        $filter = $this->normalizeFilter((string) ($this->option('module') ?? ''));
        $ranMigrations = $this->loadRanMigrations();
        $dbReady = $ranMigrations !== null;
        $entries = $this->scanEntries($moduleManager, $ranMigrations, $filter);

        if ($entries->isEmpty()) {
            $this->warn('No migration files matched the current filter.');

            return self::SUCCESS;
        }

        $this->line('Migration scan');
        $this->line('Filter : '.($filter ?? 'all'));
        $this->line('DB     : '.($dbReady ? 'connected' : 'unavailable, showing file inventory only'));
        $this->newLine();

        $pendingEntries = $entries->where('status', 'pending')->values();
        $visibleEntries = $this->option('show-all') ? $entries : $pendingEntries;

        if ($visibleEntries->isEmpty()) {
            $this->info('No pending migrations found for the selected scope.');
        } else {
            $this->table(
                ['No', 'Scope', 'State', 'Migration', 'Status'],
                $visibleEntries->map(fn (array $entry) => [
                    $entry['number'],
                    $entry['scope_label'],
                    $entry['module_state'],
                    $entry['migration'],
                    $entry['status'],
                ])->all()
            );
        }

        $this->newLine();
        $this->renderSummary($entries, $pendingEntries);
        $this->newLine();
        $this->renderSuggestedCommands($pendingEntries);

        $commandFor = trim((string) $this->option('command-for'));
        if ($commandFor !== '') {
            $this->newLine();
            $this->renderCommandsForSelection($pendingEntries, $commandFor);
        }

        return self::SUCCESS;
    }

    private function scanEntries(ModuleManager $moduleManager, ?Collection $ranMigrations, ?string $filter): Collection
    {
        $moduleStates = collect($moduleManager->all())->keyBy('slug');
        $entries = collect();
        $number = 1;

        if ($filter === null || $filter === 'core') {
            foreach ($this->migrationFilesIn(base_path('database/migrations')) as $file) {
                $entries->push($this->buildEntry(
                    $number++,
                    'core',
                    'Core',
                    'always',
                    $file,
                    base_path('database/migrations'),
                    $ranMigrations
                ));
            }
        }

        foreach (File::directories(app_path('Modules')) as $moduleDir) {
            $manifestPath = $moduleDir.DIRECTORY_SEPARATOR.'module.json';
            if (!File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) File::get($manifestPath), true);
            if (!is_array($manifest) || empty($manifest['slug'])) {
                continue;
            }

            $slug = (string) $manifest['slug'];
            if ($filter !== null && $filter !== $slug) {
                continue;
            }

            $migrationDir = ModulePath::migrationDirectory($moduleDir);
            if ($migrationDir === null) {
                continue;
            }

            $state = $moduleStates->get($slug);
            $moduleState = $state
                ? (($state['installed'] ? 'installed' : 'not-installed').'/'.($state['active'] ? 'active' : 'inactive'))
                : 'unknown';

            foreach ($this->migrationFilesIn($migrationDir) as $file) {
                $entries->push($this->buildEntry(
                    $number++,
                    $slug,
                    (string) ($manifest['name'] ?? $slug),
                    $moduleState,
                    $file,
                    $migrationDir,
                    $ranMigrations
                ));
            }
        }

        return $entries->sortBy([
            ['scope', 'asc'],
            ['migration', 'asc'],
        ])->values()->map(function (array $entry, int $index) {
            $entry['number'] = $index + 1;

            return $entry;
        })->values();
    }

    private function buildEntry(
        int $number,
        string $scope,
        string $scopeLabel,
        string $moduleState,
        string $filePath,
        string $directoryPath,
        ?Collection $ranMigrations
    ): array {
        $relativePath = $this->relativePath($filePath);
        $migration = pathinfo(basename($filePath), PATHINFO_FILENAME);
        $status = $ranMigrations === null
            ? 'unknown'
            : ($ranMigrations->contains(strtolower($migration)) ? 'applied' : 'pending');

        return [
            'number' => $number,
            'scope' => $scope,
            'scope_label' => $scopeLabel,
            'module_state' => $moduleState,
            'migration' => $migration,
            'status' => $status,
            'path' => $relativePath,
            'directory_path' => $this->relativePath($directoryPath),
            'command' => 'php artisan migrate --path='.$relativePath.' --force',
        ];
    }

    private function renderSummary(Collection $entries, Collection $pendingEntries): void
    {
        $grouped = $entries->groupBy('scope');
        $rows = [];

        foreach ($grouped as $scope => $items) {
            $pendingCount = $items->where('status', 'pending')->count();
            $rows[] = [
                $items->first()['scope_label'],
                $items->first()['module_state'],
                $items->count(),
                $pendingCount,
            ];
        }

        $this->table(['Scope', 'State', 'Files', 'Pending'], $rows);
        $this->line('Pending total: '.$pendingEntries->count());
    }

    private function renderSuggestedCommands(Collection $pendingEntries): void
    {
        if ($pendingEntries->isEmpty()) {
            $this->info('No pending migration commands to suggest.');

            return;
        }

        $this->line('Suggested commands');

        $byScope = $pendingEntries->groupBy('scope');
        foreach ($byScope as $scope => $items) {
            if ($scope === 'core') {
                $this->line('- Core all pending');
                $this->line('  php artisan migrate --path=database/migrations --force');
                continue;
            }

            $directoryPath = $items->first()['directory_path'];
            $this->line('- '.$items->first()['scope_label'].' all pending');
            $this->line('  php artisan migrate --path='.$directoryPath.' --force');
        }

        $this->line('- Specific pending item');
        $this->line('  php artisan meetra:migrate --command-for=1');
    }

    private function renderCommandsForSelection(Collection $pendingEntries, string $selection): void
    {
        $selectedNumbers = collect(explode(',', $selection))
            ->map(fn (string $value) => (int) trim($value))
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values();

        if ($selectedNumbers->isEmpty()) {
            $this->warn('No valid item numbers were given to --command-for.');

            return;
        }

        $selected = $pendingEntries
            ->whereIn('number', $selectedNumbers->all())
            ->sortBy('number')
            ->values();

        if ($selected->isEmpty()) {
            $this->warn('None of the selected numbers matched pending migrations.');

            return;
        }

        $this->line('Commands for selected pending items');
        foreach ($selected as $entry) {
            $this->line('['.$entry['number'].'] '.$entry['migration']);
            $this->line($entry['command']);
        }
    }

    private function loadRanMigrations(): ?Collection
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('migrations')) {
                return collect();
            }

            return DB::table('migrations')
                ->pluck('migration')
                ->map(fn (string $migration) => strtolower($migration))
                ->values();
        } catch (\Throwable $e) {
            $this->warn('Could not read migrations table: '.$e->getMessage());

            return null;
        }
    }

    private function migrationFilesIn(string $directory): array
    {
        if (!File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn ($file) => str_ends_with(strtolower($file->getFilename()), '.php'))
            ->map(fn ($file) => $file->getPathname())
            ->sort()
            ->values()
            ->all();
    }

    private function relativePath(string $path): string
    {
        return str_replace('\\', '/', ltrim(str_replace(base_path(), '', $path), '\\/'));
    }

    private function normalizeFilter(string $filter): ?string
    {
        $filter = trim(strtolower($filter));

        return $filter !== '' ? $filter : null;
    }
}
