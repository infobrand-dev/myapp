<div class="card card-stacked task-row">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="card-title mb-0">Task</h4>
            <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('.task-row').remove()">Remove</button>
        </div>
        <div class="row g-2 align-items-end">
            <div class="col-lg-6 col-md-6 mb-3">
                <label class="form-label">Judul Task</label>
                <input type="text" name="tasks[{{ $index }}][title]" class="form-control form-control-sm" value="{{ $task['title'] ?? '' }}" required>
            </div>
            <div class="col-lg-3 col-md-3 mb-3">
                <label class="form-label">PIC</label>
                <select name="tasks[{{ $index }}][pic]" class="form-select form-select-sm pic-select" data-selected="{{ $task['assigned_to'] ?? '' }}">
                    <option value="">Pilih PIC</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(isset($task['assigned_to']) && $task['assigned_to'] == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-3 mb-3">
                <label class="form-label">Due Date</label>
                <input type="date" name="tasks[{{ $index }}][due_date]" class="form-control form-control-sm" value="{{ isset($task['due_date']) ? \Illuminate\Support\Carbon::parse($task['due_date'])->format('Y-m-d') : '' }}">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="tasks[{{ $index }}][description]" class="form-control" rows="3">{{ $task['description'] ?? '' }}</textarea>
        </div>
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <label class="form-label mb-0">Subtasks</label>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-add-subtask">Tambah Subtask</button>
        </div>
        <div class="subtasks-container" data-index="{{ $index }}">
            @foreach(($task['subtasks'] ?? []) as $sidx => $sub)
            <div class="row g-2 align-items-center mb-2">
                <div class="col-md-4">
                    <input type="text" name="tasks[{{ $index }}][subtasks][{{ $sidx }}][title]" class="form-control form-control-sm" value="{{ $sub['title'] ?? '' }}" placeholder="Subtask title">
                </div>
                <div class="col-md-4">
                    <select name="tasks[{{ $index }}][subtasks][{{ $sidx }}][pic]" class="form-select form-select-sm pic-select" data-selected="{{ $sub['pic'] ?? '' }}">
                        <option value="">Pilih PIC</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(isset($sub['pic']) && $sub['pic'] == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="tasks[{{ $index }}][subtasks][{{ $sidx }}][due_date]" class="form-control form-control-sm" value="{{ isset($sub['due_date']) ? \Illuminate\Support\Carbon::parse($sub['due_date'])->format('Y-m-d') : '' }}">
                </div>
                <div class="col-md-1 text-end">
                    <button class="btn btn-outline-danger btn-sm" type="button" onclick="this.closest('.row').remove()">&times;</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
