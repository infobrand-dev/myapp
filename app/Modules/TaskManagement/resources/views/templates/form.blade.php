@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ isset($template) ? 'Edit Task Template' : 'Buat Task Template' }}</h2>
        <div class="text-muted">Susun daftar task + subtask, bisa ditarik ke Internal Memo.</div>
    </div>
    <a href="{{ route('tasktemplates.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ isset($template) ? route('tasktemplates.update', $template) : route('tasktemplates.store') }}">
            @csrf
            @if(isset($template)) @method('PUT') @endif
            <div class="mb-3">
                <label class="form-label">Nama Template</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $template->title ?? '') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $template->description ?? '') }}</textarea>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Daftar Task</h3>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="add-task">Tambah Task</button>
                </div>
                <div class="card-body vstack gap-3" id="tasks-wrapper">
                    @php $existingTasks = old('tasks', $template->meta['tasks'] ?? []); @endphp
                    @if(count($existingTasks))
                        @foreach($existingTasks as $idx => $t)
                            @include('taskmgmt::templates.partials.task-row', ['index' => $idx, 'task' => $t])
                        @endforeach
                    @else
                        @include('taskmgmt::templates.partials.task-row', ['index' => 0, 'task' => []])
                    @endif
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ isset($template) ? 'Simpan Perubahan' : 'Simpan Template' }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('tasktemplates.index') }}">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@include('taskmgmt::templates.partials.task-row-template')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tasksWrapper = document.getElementById('tasks-wrapper');
    const addTaskBtn = document.getElementById('add-task');

    function addTask(data = {title:'', subtasks: []}) {
        const idx = tasksWrapper.children.length;
        const tpl = document.getElementById('task-row-template').innerHTML
            .replace(/__INDEX__/g, idx)
            .replace(/__TITLE__/g, data.title || '');
        const wrapper = document.createElement('div');
        wrapper.className = 'task-row mb-3';
        wrapper.innerHTML = tpl;
        tasksWrapper.appendChild(wrapper);
        bindTaskRow(wrapper, data.subtasks || []);
    }

    function bindTaskRow(wrapper, subtasksData = []) {
        const subsContainer = wrapper.querySelector('.subtasks-container');
        const addSubBtn = wrapper.querySelector('.btn-add-subtask');
        addSubBtn.addEventListener('click', () => addSubtask(subsContainer));
        subtasksData.forEach(sub => addSubtask(subsContainer, sub.title || ''));
    }

    function addSubtask(container, title='') {
        const idx = container.dataset.index;
        const subIdx = container.children.length;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center mb-2';
        row.innerHTML = `
            <div class="col-md-9">
                <input type="text" name="tasks[${idx}][subtasks][${subIdx}][title]" class="form-control form-control-sm" placeholder="Subtask title" value="${title}">
            </div>
            <div class="col-md-2 text-end">
                <button class="btn btn-outline-danger btn-sm" type="button">&times;</button>
            </div>
        `;
        row.querySelector('button').addEventListener('click', () => row.remove());
        container.appendChild(row);
    }

    addTaskBtn.addEventListener('click', () => addTask());

    if(tasksWrapper.children.length === 0) {
        addTask();
    } else {
        tasksWrapper.querySelectorAll('.task-row').forEach(row => bindTaskRow(row));
    }
});
</script>
@endpush
