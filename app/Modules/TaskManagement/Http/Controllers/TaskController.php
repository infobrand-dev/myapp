<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Http\Requests\StoreSubtaskRequest;
use App\Modules\TaskManagement\Http\Requests\StoreTaskRequest;
use App\Modules\TaskManagement\Http\Requests\UpdateSubtaskStatusRequest;
use App\Modules\TaskManagement\Http\Requests\UpdateTaskRequest;
use App\Modules\TaskManagement\Models\Subtask;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(): View
    {
        $tasks = Task::with(['subtasks', 'assignee'])->latest()->get();

        return view('taskmgmt::index', compact('tasks'));
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->validated();

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

    public function updateStatus(Task $task, UpdateTaskRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $task->update(['status' => $validated['status']]);

        return back()->with('status', 'Task updated');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back()->with('status', 'Task deleted');
    }

    public function storeSubtask(Task $task, StoreSubtaskRequest $request): RedirectResponse
    {
        $task->subtasks()->create([
            'title'  => $request->validated()['title'],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Subtask added');
    }

    public function updateSubtaskStatus(Subtask $subtask, UpdateSubtaskStatusRequest $request): RedirectResponse
    {
        $subtask->update(['status' => $request->validated()['status']]);

        return back()->with('status', 'Subtask updated');
    }
}
