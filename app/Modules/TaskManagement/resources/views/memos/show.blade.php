@extends('layouts.admin')

@section('content')
@php
    $progress = $memo->tasks->count() ? intval($memo->tasks->where('status','done')->count() / $memo->tasks->count() * 100) : 0;
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Memo Detail</h2>
        <div class="text-muted">{{ $memo->title }}</div>
    </div>
    <div class="btn-list">
        <a class="btn btn-outline-secondary" href="{{ route('memos.index') }}">Kembali</a>
        <a class="btn btn-primary" href="{{ route('memos.edit', $memo) }}">Edit</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-secondary text-uppercase fw-bold small">Company</div>
                <div class="fs-4 fw-bold">{{ $memo->company_name }}</div>
                <div class="text-muted">{{ $memo->brand_name }}</div>
                <div class="mt-2 small text-muted">{{ $memo->address }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary text-uppercase fw-bold small mb-1">Contact</div>
                <div>{{ $memo->contact_name }} {{ $memo->job_title ? '— '.$memo->job_title : '' }}</div>
                <div class="text-muted small">{{ $memo->email }} @if($memo->phone) · {{ $memo->phone }} @endif</div>
                <div class="mt-2">
                    <span class="text-secondary text-uppercase fw-bold small">Deadline</span><br>
                    <span class="fw-semibold">{{ $memo->deadline?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="mt-2">
                    <span class="text-secondary text-uppercase fw-bold small">Account Executive</span><br>
                    <span class="fw-semibold">{{ $memo->account_executive ?? '—' }}</span>
                </div>
            </div>
        </div>
        @if($memo->note)
            <div class="mt-3">
                <div class="text-secondary text-uppercase fw-bold small mb-1">Catatan</div>
                <div class="alert alert-info mb-0">{{ $memo->note }}</div>
            </div>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="text-secondary text-uppercase fw-bold small">Progress</div>
                <div class="progress progress-xs">
                    <div class="progress-bar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}">{{ $progress }}%</div>
                </div>
            </div>
            <div class="text-muted small">{{ $memo->tasks->where('status','done')->count() }} / {{ $memo->tasks->count() }} tasks done</div>
        </div>
        @foreach($memo->tasks as $task)
            @php $task->loadMissing('subtasks'); @endphp
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold">{{ $task->title }}</div>
                        <div class="text-muted small">{{ $task->description }}</div>
                        @php
                            $subTotal = $task->subtasks->count();
                            $subDone = $task->subtasks->where('status','done')->count();
                            $subProgress = $subTotal ? intval(($subDone/$subTotal)*100) : 0;
                        @endphp
                        <div class="progress progress-xs mt-2 mb-1">
                            <div class="progress-bar bg-secondary" style="width: {{ $subProgress }}%">{{ $subProgress }}%</div>
                        </div>
                        <div class="text-muted small">{{ $subDone }} / {{ $subTotal }} subtasks done</div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-{{ $task->status === 'done' ? 'green' : ($task->status === 'in_progress' ? 'yellow' : 'gray') }}-lt text-{{ $task->status === 'done' ? 'green' : ($task->status === 'in_progress' ? 'yellow' : 'gray') }}">{{ ucfirst(str_replace('_',' ', $task->status)) }}</span>
                        <div class="text-muted small">Due: {{ $task->due_date?->format('d M') ?? '—' }}</div>
                        <a href="#" class="d-block small mt-1 text-primary edit-task"
                           data-action="{{ route('memos.tasks.update', $task) }}"
                           data-description="{{ e($task->description) }}"
                           data-status="{{ $task->status }}"
                           data-due="{{ $task->due_date?->format('Y-m-d') }}">
                           Edit
                        </a>
                    </div>
                </div>
                @if($task->subtasks->count())
                    <div class="mt-2">
                        <ul class="list-unstyled mb-0">
                            @foreach($task->subtasks as $sub)
                                <li class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge {{ $sub->status === 'done' ? 'bg-green-lt text-green' : 'bg-gray-lt text-gray' }}">{{ $sub->status === 'done' ? 'Done' : 'Pending' }}</span>
                                    <span class="{{ $sub->status === 'done' ? 'text-muted text-decoration-line-through' : '' }}">{{ $sub->title }}</span>
                                    @if($sub->pic)
                                        <span class="text-secondary small">PIC: {{ $sub->pic }}</span>
                                    @endif
                                    @if($sub->due_date)
                                        <span class="text-secondary small">Due: {{ $sub->due_date->format('d M Y') }}</span>
                                    @endif
                                    <a href="#" class="small text-primary ms-auto edit-subtask"
                                       data-action="{{ route('memos.subtasks.update', $sub) }}"
                                       data-description="{{ e($sub->title) }}"
                                       data-status="{{ $sub->status }}"
                                       data-due="{{ $sub->due_date?->format('Y-m-d') }}">
                                       Edit
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<!-- Modal edit task/subtask -->
<div class="modal modal-blur" id="editItemModal" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form method="POST" id="editItemForm">
        @csrf
        @method('PATCH')
        <div class="modal-header">
          <h5 class="modal-title" id="editItemTitle">Edit Item</h5>
          <button type="button" class="btn-close modal-close" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control" rows="3" id="editDescription"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" id="editStatus">
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="done">Done</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control" id="editDue">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('editItemModal');
    const form = document.getElementById('editItemForm');
    const desc = document.getElementById('editDescription');
    const status = document.getElementById('editStatus');
    const due = document.getElementById('editDue');
    const titleEl = document.getElementById('editItemTitle');
    const closeButtons = modalEl.querySelectorAll('.modal-close');
    let backdrop;

    function openModal(action, type, data) {
        form.setAttribute('action', action);
        desc.value = data.description || '';
        status.value = data.status || 'pending';
        due.value = data.due || '';
        titleEl.textContent = type === 'task' ? 'Edit Task' : 'Edit Subtask';
        showModal();
    }

    function showModal() {
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade';
            document.body.appendChild(backdrop);
        }
        backdrop.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        document.body.classList.add('modal-open');
    }

    function hideModal() {
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        if (backdrop) backdrop.classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    document.addEventListener('click', e => {
        const t = e.target.closest('.edit-task');
        const s = e.target.closest('.edit-subtask');
        if (t) {
            e.preventDefault();
            openModal(t.dataset.action, 'task', {
                description: t.dataset.description || '',
                status: t.dataset.status || 'pending',
                due: t.dataset.due || '',
            });
        } else if (s) {
            e.preventDefault();
            openModal(s.dataset.action, 'subtask', {
                description: s.dataset.description || '',
                status: s.dataset.status || 'pending',
                due: s.dataset.due || '',
            });
        }
        if (e.target.closest('.modal-close')) {
            e.preventDefault();
            hideModal();
        }
    });
});
</script>
@endpush
