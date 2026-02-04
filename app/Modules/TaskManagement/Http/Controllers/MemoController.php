<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Models\Memo;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\Subtask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemoController extends Controller
{
    public function index(): View
    {
        $memos = Memo::withCount(['tasks as done_tasks_count' => function ($q) {
            $q->where('status', 'done');
        }, 'tasks'])->latest()->paginate(10);

        return view('taskmgmt::memos.index', compact('memos'));
    }

    public function create(): View
    {
        $users = \App\Models\User::select('id', 'name')->orderBy('name')->get();
        return view('taskmgmt::memos.form', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $tasksData = $this->tasksFromRequest($request);

        $memo = Memo::create($data);
        $this->syncTasks($memo, $tasksData, $request->user()->id);

        return redirect()->route('memos.index')->with('status', 'Memo created');
    }

    public function show(Memo $memo): View
    {
        $memo->load(['tasks.subtasks']);
        return view('taskmgmt::memos.show', compact('memo'));
    }

    public function edit(Memo $memo): View
    {
        $memo->load('tasks.subtasks');
        $users = \App\Models\User::select('id', 'name')->orderBy('name')->get();
        return view('taskmgmt::memos.form', compact('memo', 'users'));
    }

    public function update(Request $request, Memo $memo): RedirectResponse
    {
        $data = $this->validated($request);
        $tasksData = $this->tasksFromRequest($request);

        $memo->update($data);
        $memo->tasks()->delete();
        $this->syncTasks($memo, $tasksData, $request->user()->id);

        return redirect()->route('memos.index')->with('status', 'Memo updated');
    }

    public function destroy(Memo $memo): RedirectResponse
    {
        $memo->delete();
        return back()->with('status', 'Memo deleted');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'account_executive' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function tasksFromRequest(Request $request): array
    {
        return $request->validate([
            'tasks' => ['array'],
            'tasks.*.title' => ['required_with:tasks', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string'],
            'tasks.*.due_date' => ['nullable', 'date'],
            'tasks.*.pic' => ['nullable', 'exists:users,id'],
            'tasks.*.subtasks' => ['array'],
            'tasks.*.subtasks.*.title' => ['required_with:tasks.*.subtasks', 'string', 'max:255'],
            'tasks.*.subtasks.*.pic' => ['nullable', 'string', 'max:255'],
            'tasks.*.subtasks.*.due_date' => ['nullable', 'date'],
        ])['tasks'] ?? [];
    }

    private function syncTasks(Memo $memo, array $tasksData, int $userId): void
    {
        foreach ($tasksData as $t) {
            $task = $memo->tasks()->create([
                'title' => $t['title'],
                'description' => $t['description'] ?? null,
                'due_date' => $t['due_date'] ?? null,
                'status' => 'pending',
                'assigned_to' => $t['pic'] ?? $userId,
            ]);
            foreach ($t['subtasks'] ?? [] as $sub) {
                $task->subtasks()->create([
                    'title' => $sub['title'],
                    'pic' => $sub['pic'] ?? null,
                    'due_date' => $sub['due_date'] ?? null,
                    'status' => 'pending',
                ]);
            }
        }
    }

    public function updateTask(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,in_progress,done'],
            'due_date' => ['nullable', 'date'],
        ]);
        $task->update($data);
        return back()->with('status', 'Task updated');
    }

    public function updateSubtask(Request $request, Subtask $subtask): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'status' => ['required', 'in:pending,in_progress,done'],
            'due_date' => ['nullable', 'date'],
        ]);
        $subtask->update($data);
        return back()->with('status', 'Subtask updated');
    }
}
