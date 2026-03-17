<?php

namespace App\Modules\SampleData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SampleData\Support\SampleDataRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class SampleDataController extends Controller
{
    public function index(SampleDataRegistry $registry): View
    {
        return view('sample-data::index', [
            'modules' => $registry->activeModules(),
        ]);
    }

    public function store(string $slug, SampleDataRegistry $registry): RedirectResponse
    {
        try {
            $result = $registry->seed($slug);

            return back()->with('status', $result);
        } catch (Throwable $e) {
            return back()->with('status', "Gagal generate sample data untuk module '{$slug}': " . $e->getMessage());
        }
    }
}
