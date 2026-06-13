<?php

namespace App\Http\Controllers;

use App\Support\ModuleManager;
use App\Support\ModuleFilesystemAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Throwable;

class ModuleController extends Controller
{
    public function index(ModuleManager $modules, ModuleFilesystemAudit $filesystemAudit): View
    {
        $allModules = collect($modules->all())
            ->map(function (array $module) use ($filesystemAudit): array {
                $issues = $filesystemAudit->issuesForModule($module);
                $module['filesystem_issues'] = $issues;
                $module['has_filesystem_issues'] = !empty($issues);

                return $module;
            })
            ->all();
        $filters = [
            'category' => trim((string) request('category', '')),
            'status' => trim((string) request('status', '')),
            'search' => trim((string) request('search', '')),
        ];

        $categories = collect($allModules)
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $filteredModules = collect($allModules)
            ->filter(function (array $module) use ($filters) {
                if ($filters['category'] !== '' && $module['category'] !== $filters['category']) {
                    return false;
                }

                if ($filters['status'] !== '') {
                    $status = !$module['installed']
                        ? 'not-installed'
                        : ($module['has_filesystem_issues']
                            ? 'filesystem-issue'
                        : ($module['has_pending_db_update']
                            ? 'pending-db-update'
                            : ($module['active'] ? 'active' : 'installed')));

                    if ($status !== $filters['status']) {
                        return false;
                    }
                }

                if ($filters['search'] !== '') {
                    $needle = strtolower($filters['search']);
                    $haystack = strtolower(implode(' ', [
                        $module['name'],
                        $module['slug'],
                        $module['description'],
                        $module['category'],
                    ]));

                    if (!str_contains($haystack, $needle)) {
                        return false;
                    }
                }

                return true;
            })
            ->all();

        return view('modules.index', [
            'modules' => $filteredModules,
            'moduleRegistry' => array_values($allModules),
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function install(Request $request, string $slug, ModuleManager $modules): RedirectResponse|JsonResponse
    {
        try {
            $modules->install($slug);

            return $this->moduleActionSuccessResponse($request, $slug, "Module '{$slug}' berhasil di-install.");
        } catch (Throwable $e) {
            return $this->moduleActionErrorResponse($request, $slug, "Gagal install module '{$slug}': " . $e->getMessage());
        }
    }

    public function activate(Request $request, string $slug, ModuleManager $modules): RedirectResponse|JsonResponse
    {
        try {
            $modules->activate($slug);

            return $this->moduleActionSuccessResponse($request, $slug, "Module '{$slug}' berhasil diaktifkan.");
        } catch (Throwable $e) {
            return $this->moduleActionErrorResponse($request, $slug, "Gagal aktivasi module '{$slug}': " . $e->getMessage());
        }
    }

    public function deactivate(Request $request, string $slug, ModuleManager $modules): RedirectResponse|JsonResponse
    {
        try {
            $modules->deactivate($slug);

            return $this->moduleActionSuccessResponse($request, $slug, "Module '{$slug}' berhasil dinonaktifkan.");
        } catch (Throwable $e) {
            return $this->moduleActionErrorResponse($request, $slug, "Gagal nonaktifkan module '{$slug}': " . $e->getMessage());
        }
    }

    public function runDbUpdate(string $slug, Request $request, ModuleManager $modules): RedirectResponse|JsonResponse
    {
        try {
            $count = $modules->runPendingDbUpdate($slug, $request->user()?->id);
            $message = $count > 0
                ? "DB update module '{$slug}' berhasil. {$count} migration dijalankan."
                : "Module '{$slug}' tidak memiliki pending DB update.";

            return $this->moduleActionSuccessResponse($request, $slug, $message, ['count' => $count]);
        } catch (Throwable $e) {
            return $this->moduleActionErrorResponse($request, $slug, "Gagal DB update module '{$slug}': " . $e->getMessage());
        }
    }

    public function runSingleMigration(string $slug, string $migration, Request $request, ModuleManager $modules): RedirectResponse|JsonResponse
    {
        try {
            $modules->runSingleMigration($slug, $migration, $request->user()?->id);

            return $this->moduleActionSuccessResponse(
                $request,
                $slug,
                "Migration '{$migration}' untuk module '{$slug}' berhasil dijalankan.",
                ['migration' => $migration]
            );
        } catch (Throwable $e) {
            return $this->moduleActionErrorResponse(
                $request,
                $slug,
                "Gagal menjalankan migration '{$migration}' untuk module '{$slug}': " . $e->getMessage(),
                ['migration' => $migration]
            );
        }
    }

    public function bulk(Request $request, ModuleManager $modules): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:install,activate,deactivate,db-update'],
            'slugs' => ['required', 'array', 'min:1'],
            'slugs.*' => ['string'],
        ]);

        if (!$this->canRunBulkAction($request, $data['action'])) {
            abort(403);
        }

        try {
            $result = $modules->runBulkAction($data['action'], Arr::wrap($data['slugs']), $request->user()?->id);

            return back()->with('status', $this->bulkStatusMessage($result));
        } catch (Throwable $e) {
            return back()->with('status', 'Gagal bulk action module: ' . $e->getMessage());
        }
    }

    private function canRunBulkAction(Request $request, string $action): bool
    {
        return match ($action) {
            'install' => (bool) $request->user()?->can('modules.install'),
            'activate', 'db-update' => (bool) $request->user()?->can('modules.activate'),
            'deactivate' => (bool) $request->user()?->can('modules.deactivate'),
            default => false,
        };
    }

    /**
     * @param  array{requested: array<int, string>, expanded: array<int, string>, executed: array<int, string>, action: string}  $result
     */
    private function bulkStatusMessage(array $result): string
    {
        $labels = [
            'install' => 'install',
            'activate' => 'aktivasi',
            'deactivate' => 'nonaktif',
            'db-update' => 'DB update',
        ];

        $executed = count($result['executed']);
        $requested = implode(', ', $result['requested']);
        $autoIncluded = array_values(array_diff($result['expanded'], $result['requested']));
        $autoLabel = $autoIncluded !== []
            ? ' Dependency otomatis ikut: ' . implode(', ', $autoIncluded) . '.'
            : '';

        if ($executed === 0) {
            return "Bulk {$labels[$result['action']]} selesai. Tidak ada perubahan untuk pilihan: {$requested}.{$autoLabel}";
        }

        return "Bulk {$labels[$result['action']]} berhasil untuk {$executed} module: " . implode(', ', $result['executed']) . ".{$autoLabel}";
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function moduleActionSuccessResponse(Request $request, string $slug, string $message, array $extra = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'ok' => true,
                'slug' => $slug,
                'message' => $message,
            ], $extra));
        }

        return back()->with('status', $message);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function moduleActionErrorResponse(Request $request, string $slug, string $message, array $extra = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'ok' => false,
                'slug' => $slug,
                'message' => $message,
            ], $extra), 422);
        }

        return back()->with('status', $message);
    }
}
