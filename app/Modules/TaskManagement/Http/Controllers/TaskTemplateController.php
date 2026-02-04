<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Models\TaskTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskTemplateController extends Controller
{
    public function index(): View
    {
        $templates = TaskTemplate::with('items')->latest()->get();
        return view('taskmgmt::templates.index', compact('templates'));
    }

    public function list(): JsonResponse
    {
        $items = TaskTemplate::select('id', 'title', 'description')
            ->orderBy('title')
            ->get();
        return response()->json($items);
    }

    public function show(TaskTemplate $template): JsonResponse
    {
        $template->load('items');
        return response()->json([
            'id' => $template->id,
            'title' => $template->title,
            'description' => $template->description,
            'items' => $template->items->sortBy('position')->values()->map(function ($item) {
                return [
                    'title' => $item->title,
                    'position' => $item->position,
                ];
            }),
        ]);
    }

    public function create(): View
    {
        return view('taskmgmt::templates.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tasks' => ['array'],
            'tasks.*.title' => ['required_with:tasks', 'string', 'max:255'],
            'tasks.*.subtasks' => ['array'],
            'tasks.*.subtasks.*.title' => ['required_with:tasks.*.subtasks', 'string', 'max:255'],
        ]);

        $template = TaskTemplate::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'meta' => ['tasks' => $data['tasks'] ?? []],
        ]);

        $this->syncItems($template, $data['tasks'] ?? []);

        return redirect()->route('tasktemplates.index')->with('status', 'Template created');
    }

    public function edit(TaskTemplate $template): View
    {
        $itemsText = $template->items->sortBy('position')->pluck('title')->join("\n");
        return view('taskmgmt::templates.form', compact('template', 'itemsText'));
    }

    public function update(TaskTemplate $template, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tasks' => ['array'],
            'tasks.*.title' => ['required_with:tasks', 'string', 'max:255'],
            'tasks.*.subtasks' => ['array'],
            'tasks.*.subtasks.*.title' => ['required_with:tasks.*.subtasks', 'string', 'max:255'],
        ]);

        $template->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'meta' => ['tasks' => $data['tasks'] ?? []],
        ]);

        $template->items()->delete();
        $this->syncItems($template, $data['tasks'] ?? []);

        return redirect()->route('tasktemplates.index')->with('status', 'Template updated');
    }

    public function destroy(TaskTemplate $template): RedirectResponse
    {
        $template->delete();
        return back()->with('status', 'Template deleted');
    }

    private function syncItems(TaskTemplate $template, array $tasks): void
    {
        $titles = collect($tasks)->pluck('title')->filter();
        foreach ($titles as $index => $title) {
            $template->items()->create([
                'title' => $title,
                'position' => $index + 1,
            ]);
        }
    }
}
