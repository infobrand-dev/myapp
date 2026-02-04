<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Models\Subtask;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(): View
    {
        $tasks = Task::with(['subtasks', 'assignee'])->latest()->get();

        return view('taskmgmt::index', compact('tasks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'subtasks' => ['array'],
            'subtasks.*.title' => ['required_with:subtasks', 'string', 'max:255'],
            'subtasks.*.pic' => ['nullable', 'string', 'max:255'],
            'subtasks.*.due_date' => ['nullable', 'date'],
        ]);

        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => 'pending',
            'assigned_to' => $request->user()->id,
        ]);

        foreach ($data['subtasks'] ?? [] as $sub) {
            $task->subtasks()->create([
                'title' => $sub['title'],
                'pic' => $sub['pic'] ?? null,
                'due_date' => $sub['due_date'] ?? null,
                'status' => 'pending',
            ]);
        }

        return back()->with('status', 'Task created');
    }

    public function updateStatus(Task $task, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,done'],
        ]);

        $task->update(['status' => $validated['status']]);

        return back()->with('status', 'Task updated');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back()->with('status', 'Task deleted');
    }

    public function storeSubtask(Task $task, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $task->subtasks()->create([
            'title' => $data['title'],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Subtask added');
    }

    public function updateSubtaskStatus(Subtask $subtask, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,done'],
        ]);

        $subtask->update(['status' => $validated['status']]);

        return back()->with('status', 'Subtask updated');
    }
}
