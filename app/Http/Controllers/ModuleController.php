<?php

namespace App\Http\Controllers;

use App\Support\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ModuleController extends Controller
{
    public function index(ModuleManager $modules): View
    {
        $allModules = $modules->all();
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
                        : ($module['active'] ? 'active' : 'installed');

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
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function install(string $slug, ModuleManager $modules): RedirectResponse
    {
        try {
            $modules->install($slug);
            return back()->with('status', "Module '{$slug}' berhasil di-install.");
        } catch (Throwable $e) {
            return back()->with('status', "Gagal install module '{$slug}': " . $e->getMessage());
        }
    }

    public function activate(string $slug, ModuleManager $modules): RedirectResponse
    {
        try {
            $modules->activate($slug);
            return back()->with('status', "Module '{$slug}' berhasil diaktifkan.");
        } catch (Throwable $e) {
            return back()->with('status', "Gagal aktivasi module '{$slug}': " . $e->getMessage());
        }
    }

    public function deactivate(string $slug, ModuleManager $modules): RedirectResponse
    {
        try {
            $modules->deactivate($slug);
            return back()->with('status', "Module '{$slug}' berhasil dinonaktifkan.");
        } catch (Throwable $e) {
            return back()->with('status', "Gagal nonaktifkan module '{$slug}': " . $e->getMessage());
        }
    }
}
