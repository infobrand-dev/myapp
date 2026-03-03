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
        return view('modules.index', [
            'modules' => $modules->all(),
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

