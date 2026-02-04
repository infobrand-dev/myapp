@extends('layouts.admin')

@section('content')
<form method="POST" action="{{ isset($memo) ? route('memos.update', $memo) : route('memos.store') }}">
    @csrf
    @if(isset($memo)) @method('PUT') @endif
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">{{ isset($memo) ? 'Edit Memo' : 'Buat Memo' }}</h2>
            <div class="text-muted">Form pekerjaan dengan detail company, PIC, AE, dan task list.</div>
        </div>
        <div class="btn-list">
            <a href="{{ route('memos.index') }}" class="btn btn-outline-secondary">Kembali</a>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Task Detail</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Judul</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $memo->title ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deadline</label>
                            <input type="date" name="deadline" class="form-control" value="{{ old('deadline', isset($memo) && $memo->deadline ? $memo->deadline->format('Y-m-d') : '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Executive</label>
                            <input type="text" name="account_executive" class="form-control" value="{{ old('account_executive', $memo->account_executive ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="note" class="form-control" rows="3">{{ old('note', $memo->note ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-3" style="border-color: #dbeafe; background: #f8fbff;">
                <div class="card-header"><h3 class="card-title mb-0">Company Detail</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Perusahaan</label>
                            <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $memo->company_name ?? '') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Brand (opsional)</label>
                            <input type="text" name="brand_name" class="form-control" value="{{ old('brand_name', $memo->brand_name ?? '') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $memo->email ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nomor HP/WhatsApp</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $memo->phone ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Alamat</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $memo->address ?? '') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Kontak</label>
                            <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', $memo->contact_name ?? '') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="job_title" class="form-control" value="{{ old('job_title', $memo->job_title ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Daftar Task</h3>
            <div class="d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal">Pilih Template</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="add-task">Tambah Task</button>
            </div>
        </div>
        <div class="card-body vstack gap-3" id="tasks-wrapper">
            @php $existingTasks = old('tasks', isset($memo) ? $memo->tasks->toArray() : []); @endphp
            @if(count($existingTasks))
                @foreach($existingTasks as $idx => $t)
                    @include('taskmgmt::memos.partials.task-row', ['index' => $idx, 'task' => $t])
                @endforeach
            @else
                @include('taskmgmt::memos.partials.task-row', ['index' => 0, 'task' => []])
            @endif
        </div>
    </div>
</form>

<!-- Template chooser modal -->
<div class="modal modal-blur fade" id="templateModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pilih Task Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="template-list" class="list-group list-group-flush">
          <div class="text-muted small">Memuat template...</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@include('taskmgmt::memos.partials.task-row-template')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const picOptions = @json(($users ?? \App\Models\User::select('id','name')->orderBy('name')->get())->map(fn($u) => ['id'=>$u->id, 'name'=>$u->name]));
    const tasksWrapper = document.getElementById('tasks-wrapper');
    const addTaskBtn = document.getElementById('add-task');
    const templateList = document.getElementById('template-list');

    function addTask(data = {title:'', description:'', due_date:'', subtasks: []}) {
        const idx = tasksWrapper.children.length;
        const tpl = document.getElementById('task-row-template').innerHTML
            .replace(/__INDEX__/g, idx)
            .replace(/__TITLE__/g, data.title || '')
            .replace(/__DESC__/g, data.description || '')
            .replace(/__DUE__/g, data.due_date || '');
        const wrapper = document.createElement('div');
        wrapper.className = 'task-row mb-3';
        wrapper.innerHTML = tpl;
        tasksWrapper.appendChild(wrapper);
        bindTaskRow(wrapper, data.subtasks || []);
    }

    function bindTaskRow(wrapper, subtasksData = []) {
        const subsContainer = wrapper.querySelector('.subtasks-container');
        const addSubBtn = wrapper.querySelector('.btn-add-subtask');
        if (addSubBtn) {
            addSubBtn.addEventListener('click', () => addSubtask(subsContainer));
        }
        const picSelect = wrapper.querySelector('.pic-select');
        if (picSelect) fillPicSelect(picSelect, picSelect.dataset.selected || '');
        subtasksData.forEach(sub => addSubtask(subsContainer, sub.title || '', sub.pic || '', sub.due_date || ''));
    }

    function fillPicSelect(selectEl, selectedId = '') {
        selectEl.innerHTML = '<option value=\"\">Pilih PIC</option>';
        picOptions.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.id;
            option.textContent = opt.name;
            if (String(opt.id) === String(selectedId)) option.selected = true;
            selectEl.appendChild(option);
        });
    }

    function addSubtask(container, title='', pic='', dueDate='') {
        const idx = container.dataset.index;
        const subIdx = container.children.length;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center mb-2';
        row.innerHTML = `
            <div class="col-md-4">
                <input type="text" name="tasks[${idx}][subtasks][${subIdx}][title]" class="form-control form-control-sm" placeholder="Subtask title" value="${title}">
            </div>
            <div class="col-md-4">
                <select name="tasks[${idx}][subtasks][${subIdx}][pic]" class="form-select form-select-sm pic-select" data-selected="${pic}">
                    <option value="">Pilih PIC</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="tasks[${idx}][subtasks][${subIdx}][due_date]" class="form-control form-control-sm" value="${dueDate}">
            </div>
            <div class="col-md-1 text-end">
                <button class="btn btn-outline-danger btn-sm" type="button">&times;</button>
            </div>
        `;
        row.querySelector('button').addEventListener('click', () => row.remove());
        container.appendChild(row);
        const selectEl = row.querySelector('.pic-select');
        fillPicSelect(selectEl, pic);
    }

    addTaskBtn.addEventListener('click', () => addTask());

    // Load task templates into modal list
    fetch('{{ route('tasktemplates.list') }}', {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r => r.json())
        .then(list => {
            templateList.innerHTML = '';
            list.forEach(item => {
                const a = document.createElement('a');
                a.href = "#";
                a.className = "list-group-item list-group-item-action";
                a.dataset.id = item.id;
                a.innerHTML = `
                    <div class="fw-bold">${item.title}</div>
                    <div class="text-muted small mb-1">${item.description ?? ''}</div>
                `;
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    applyTemplate(item.id);
                });
                templateList.appendChild(a);
            });
            if (!list.length) {
                templateList.innerHTML = '<div class="text-muted small">Belum ada template</div>';
            }
        })
        .catch(() => {
            templateList.innerHTML = '<div class="text-danger small">Gagal memuat template</div>';
        });

    function applyTemplate(templateId) {
        fetch(`{{ url('/task-templates') }}/${templateId}`, {headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json())
            .then(tpl => {
                tasksWrapper.innerHTML = '';
                (tpl.items || []).forEach(item => addTask({title:item.title, description:'', due_date:'', subtasks: []}));
                if (!tpl.items || !tpl.items.length) addTask();
                const modalEl = document.getElementById('templateModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal?.hide();
            })
            .catch(() => {});
    }

    if(tasksWrapper.children.length === 0) {
        addTask();
    } else {
        tasksWrapper.querySelectorAll('.task-row').forEach(row => bindTaskRow(row));
    }
});
</script>
@endpush
