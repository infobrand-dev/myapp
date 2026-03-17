<?php

namespace App\Modules\SampleData\Support;

use App\Support\ModuleManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SampleDataRegistry
{
    private ModuleManager $modules;

    public function __construct(ModuleManager $modules)
    {
        $this->modules = $modules;
    }

    public function activeModules(): Collection
    {
        $manifests = $this->manifestsBySlug();

        return collect($this->modules->all())
            ->filter(fn (array $module) => $module['installed'] && $module['active'] && $module['slug'] !== 'sample_data')
            ->map(function (array $module) use ($manifests) {
                $manifest = $manifests[$module['slug']] ?? [];
                $sampleData = $manifest['sample_data'] ?? [];
                $seeders = array_values(array_filter((array) ($sampleData['seeders'] ?? []), 'is_string'));

                return [
                    'slug' => $module['slug'],
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'sample_description' => (string) ($sampleData['description'] ?? ''),
                    'seeders' => $seeders,
                    'ready' => !empty($seeders),
                ];
            })
            ->sortBy('name')
            ->values();
    }

    public function seed(string $slug): string
    {
        $module = $this->activeModules()->firstWhere('slug', $slug);

        if (!$module) {
            throw new RuntimeException("Module '{$slug}' tidak aktif atau tidak tersedia.");
        }

        if (!$module['ready']) {
            throw new RuntimeException("Module '{$slug}' belum mendaftarkan sample data di module.json.");
        }

        foreach ($module['seeders'] as $seederClass) {
            if (!class_exists($seederClass)) {
                throw new RuntimeException("Seeder class '{$seederClass}' tidak ditemukan.");
            }

            $exitCode = Artisan::call('db:seed', [
                '--class' => $seederClass,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                $output = trim((string) Artisan::output());
                throw new RuntimeException("Seeder '{$seederClass}' gagal dijalankan. {$output}");
            }
        }

        return "Sample data untuk module '{$slug}' berhasil dibuat.";
    }

    private function manifestsBySlug(): array
    {
        $root = app_path('Modules');
        if (!File::isDirectory($root)) {
            return [];
        }

        $manifests = [];
        foreach (File::directories($root) as $moduleDir) {
            $manifestPath = $moduleDir . DIRECTORY_SEPARATOR . 'module.json';
            if (!File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) File::get($manifestPath), true);
            if (!is_array($manifest) || empty($manifest['slug'])) {
                continue;
            }

            $manifests[(string) $manifest['slug']] = $manifest;
        }

        return $manifests;
    }
}
