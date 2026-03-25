<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\TaskManagement\Http\Requests\StoreMemoRequest;
use App\Modules\TaskManagement\Http\Requests\UpdateMemoRequest;
use App\Modules\TaskManagement\Models\Memo;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\Subtask;
use App\Modules\TaskManagement\Http\Requests\UpdateMemoSubtaskRequest;
use App\Modules\TaskManagement\Http\Requests\UpdateMemoTaskRequest;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemoController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $memos = Memo::query()
            ->where('tenant_id', $this->tenantId())
            ->withCount(['tasks as done_tasks_count' => function ($q) {
                $q->where('status', 'done');
            }, 'tasks'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereFullText(
                        ['title', 'company_name', 'brand_name', 'contact_name', 'job_title', 'account_executive', 'note'],
                        $search
                    )->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('deadline')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('taskmgmt::memos.index', compact('memos'));
    }

    public function create(): View
    {
        $users = User::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        return view('taskmgmt::memos.form', compact('users'));
    }

    public function store(StoreMemoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tasksData = $validated['tasks'] ?? [];
        $data = collect($validated)->except('tasks')->all();

        $memo = Memo::create([
            ...$data,
            'tenant_id' => $this->tenantId(),
        ]);
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
        $users = User::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        return view('taskmgmt::memos.form', compact('memo', 'users'));
    }

    public function update(UpdateMemoRequest $request, Memo $memo): RedirectResponse
    {
        $validated = $request->validated();
        $tasksData = $validated['tasks'] ?? [];
        $data = collect($validated)->except('tasks')->all();

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

    private function syncTasks(Memo $memo, array $tasksData, int $userId): void
    {
        foreach ($tasksData as $t) {
            $task = $memo->tasks()->create([
                'tenant_id' => $this->tenantId(),
                'title' => $t['title'],
                'description' => $t['description'] ?? null,
                'due_date' => $t['due_date'] ?? null,
                'status' => 'pending',
                'assigned_to' => $t['pic'] ?? $userId,
            ]);
            foreach ($t['subtasks'] ?? [] as $sub) {
                $task->subtasks()->create([
                    'tenant_id' => $this->tenantId(),
                    'title' => $sub['title'],
                    'pic' => $sub['pic'] ?? null,
                    'due_date' => $sub['due_date'] ?? null,
                    'status' => 'pending',
                ]);
            }
        }
    }

    public function updateTask(UpdateMemoTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());
        return back()->with('status', 'Task updated');
    }

    public function updateSubtask(UpdateMemoSubtaskRequest $request, Subtask $subtask): RedirectResponse
    {
        $subtask->update($request->validated());
        return back()->with('status', 'Subtask updated');
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }
}
