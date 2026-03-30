@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-end gap-3">
        <div>
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">CRM</h2>
        </div>
        <div class="btn-list">
            <a href="{{ route('crm.index', array_merge(request()->query(), ['view' => 'list'])) }}" class="btn {{ $viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="ti ti-list me-1"></i>List
            </a>
            <a href="{{ route('crm.index', array_merge(request()->query(), ['view' => 'kanban'])) }}" class="btn {{ $viewMode === 'kanban' ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="ti ti-layout-kanban me-1"></i>Kanban
            </a>
            <a href="{{ route('crm.create') }}" class="btn btn-dark">
                <i class="ti ti-plus me-1"></i>Tambah Lead
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-primary-lt text-primary rounded-3"><i class="ti ti-users fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Total Lead</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $summary['total'] }}</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-azure-lt text-azure rounded-3"><i class="ti ti-activity fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Open Pipeline</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $summary['open'] }}</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-green-lt text-green rounded-3"><i class="ti ti-trophy fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Won</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $summary['won'] }}</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-orange-lt text-orange rounded-3"><i class="ti ti-coin fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Estimated Value</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $money->format((float) $summary['value']) }}</div></div></div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('crm.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="col-lg-4">
                <label class="form-label">Cari</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-search text-muted"></i></span>
                    <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Judul, contact, email, atau catatan">
                </div>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Stage</label>
                <select name="stage" class="form-select">
                    <option value="">Semua stage</option>
                    @foreach($stageOptions as $key => $label)
                        <option value="{{ $key }}" @selected($filters['stage'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Owner</label>
                <select name="owner_user_id" class="form-select">
                    <option value="">Semua owner</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((int) $filters['owner_user_id'] === (int) $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 d-flex align-items-center pt-lg-4">
                <label class="form-check mb-0">
                    <input type="checkbox" name="show_archived" value="1" class="form-check-input" @checked($filters['show_archived'])>
                    <span class="form-check-label">Tampilkan arsip</span>
                </label>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('crm.index', ['view' => $viewMode]) }}" class="btn btn-outline-secondary" title="Reset filter"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

@if($viewMode === 'kanban')
<div class="crm-kanban-wrap">
    <div class="crm-board" id="crm-kanban-board">
        @foreach($board as $column)
            @php
                $colValue = (float) $column['items']->sum('estimated_value');
            @endphp
            <div class="crm-column" data-stage="{{ $column['key'] }}" data-stage-label="{{ $column['label'] }}">
                <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                    <div class="min-width-0">
                        <div class="fw-semibold" style="font-size:.85rem;">{{ $column['label'] }}</div>
                        <div class="text-muted" style="font-size:.72rem;" data-col-count>
                            {{ $column['items']->count() }} lead
                            @if($colValue > 0) · {{ $money->format($colValue) }}@endif
                        </div>
                    </div>
                    <span class="badge {{ $column['badge_class'] }} flex-shrink-0">{{ $column['items']->count() }}</span>
                </div>

                <div class="crm-cards">
                    @forelse($column['items'] as $lead)
                        @php
                            $isOverdue = $lead->next_follow_up_at && $lead->next_follow_up_at->isPast();
                            $priorityBar = match($lead->priority) {
                                'low' => 'crm-priority-low',
                                'medium' => 'crm-priority-medium',
                                'high' => 'crm-priority-high',
                                'urgent' => 'crm-priority-urgent',
                                default => 'crm-priority-medium',
                            };
                        @endphp
                        <div class="crm-card" draggable="true" data-lead-id="{{ $lead->id }}" data-value="{{ (float) ($lead->estimated_value ?? 0) }}">
                            <div class="d-flex gap-2">
                                <div class="crm-priority-bar {{ $priorityBar }}"></div>
                                <div class="flex-fill min-width-0">
                                    <a href="{{ route('crm.show', $lead) }}" class="crm-card-title text-decoration-none d-block mb-1">{{ $lead->title }}</a>
                                    @if($lead->contact)
                                        <div class="text-muted mb-1" style="font-size:.75rem;"><i class="ti ti-user me-1"></i>{{ $lead->contact->name }}</div>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                                        <span class="text-muted" style="font-size:.75rem;">{{ $money->format((float) ($lead->estimated_value ?? 0), $lead->currency) }}</span>
                                        @if($lead->probability)
                                            <span class="badge bg-secondary-lt text-secondary" style="font-size:.65rem;">{{ $lead->probability }}%</span>
                                        @endif
                                    </div>
                                    @if($lead->next_follow_up_at)
                                        <div class="{{ $isOverdue ? 'text-danger fw-semibold' : 'text-muted' }}" style="font-size:.72rem;">
                                            <i class="ti ti-calendar-event me-1"></i>{{ $lead->next_follow_up_at->translatedFormat('d M H:i') }}
                                            @if($isOverdue)<span class="badge bg-red-lt text-red ms-1">Overdue</span>@endif
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                        <div class="text-muted" style="font-size:.72rem;"><i class="ti ti-user-circle me-1"></i>{{ $lead->owner?->name ?? 'No owner' }}</div>
                                        <a href="{{ route('crm.edit', $lead) }}" class="btn btn-ghost-secondary btn-sm py-0 px-1" title="Edit">
                                            <i class="ti ti-pencil" style="font-size:.8rem;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4 crm-empty-col" style="font-size:.8rem; border:2px dashed var(--tblr-card-border-color); border-radius:.75rem;">
                            <i class="ti ti-inbox d-block mb-1" style="font-size:1.4rem;"></i>
                            Belum ada lead
                        </div>
                    @endforelse
                </div>

                <div class="mt-2">
                    <a href="{{ route('crm.create', ['stage' => $column['key']]) }}" class="btn btn-ghost-secondary w-100 btn-sm" style="font-size:.75rem;">
                        <i class="ti ti-plus me-1"></i>Tambah di sini
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@else
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Contact</th>
                    <th>Stage</th>
                    <th>Priority</th>
                    <th>Owner</th>
                    <th>Value</th>
                    <th>Follow Up</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                    @php
                        $isOverdue = $lead->next_follow_up_at && $lead->next_follow_up_at->isPast();
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('crm.show', $lead) }}" class="text-decoration-none fw-semibold text-body">{{ $lead->title }}</a>
                            @if($lead->lead_source)
                                <div class="small text-muted">{{ $lead->lead_source }}</div>
                            @endif
                            @if($lead->is_archived)
                                <span class="badge bg-secondary-lt text-secondary">Arsip</span>
                            @endif
                        </td>
                        <td>
                            @if($lead->contact)
                                <div>{{ $lead->contact->name }}</div>
                                @if($lead->contact->email)
                                    <div class="small text-muted">{{ $lead->contact->email }}</div>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><span class="badge {{ \App\Modules\Crm\Support\CrmStageCatalog::badgeClass($lead->stage) }}">{{ $stageOptions[$lead->stage] ?? $lead->stage }}</span></td>
                        <td><span class="badge {{ \App\Modules\Crm\Support\CrmStageCatalog::priorityBadgeClass($lead->priority) }}">{{ \Illuminate\Support\Str::headline($lead->priority) }}</span></td>
                        <td>{{ $lead->owner?->name ?? '-' }}</td>
                        <td>
                            <span class="fw-semibold">{{ $money->format((float) ($lead->estimated_value ?? 0), $lead->currency) }}</span>
                            @if($lead->probability)
                                <div class="small text-muted">{{ $lead->probability }}%</div>
                            @endif
                        </td>
                        <td>
                            @if($lead->next_follow_up_at)
                                <span class="{{ $isOverdue ? 'text-danger fw-semibold' : '' }}">{{ $lead->next_follow_up_at->translatedFormat('d M Y H:i') }}</span>
                                @if($isOverdue)
                                    <div><span class="badge bg-red-lt text-red">Overdue</span></div>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('crm.show', $lead) }}" class="btn btn-icon btn-ghost-secondary" title="Detail"><i class="ti ti-eye"></i></a>
                                <a href="{{ route('crm.edit', $lead) }}" class="btn btn-icon btn-ghost-secondary" title="Edit"><i class="ti ti-pencil"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="text-center py-5">
                                <i class="ti ti-users-group d-block mx-auto mb-2" style="font-size:2.5rem; color:var(--brand-gray-300);"></i>
                                <div class="text-muted fw-medium">Belum ada lead CRM.</div>
                                <div class="text-muted small mb-3">Mulai dengan menambahkan lead pertama.</div>
                                <a href="{{ route('crm.create') }}" class="btn btn-primary">
                                    <i class="ti ti-plus me-1"></i>Tambah Lead Pertama
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leads?->hasPages())
        <div class="card-footer">{{ $leads->links() }}</div>
    @endif
</div>
@endif
@endsection

@push('scripts')
@if($viewMode === 'kanban')
<script>
(function () {
    const board = document.getElementById('crm-kanban-board');
    if (!board) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    let draggedEl = null;
    let draggedId = null;

    board.addEventListener('dragstart', e => {
        const card = e.target.closest('[data-lead-id]');
        if (!card) return;
        draggedEl = card;
        draggedId = card.dataset.leadId;
        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });

    board.addEventListener('dragend', () => {
        draggedEl?.classList.remove('dragging');
        board.querySelectorAll('.crm-column').forEach(c => c.classList.remove('drag-over'));
        draggedEl = draggedId = null;
    });

    board.addEventListener('dragover', e => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const col = e.target.closest('.crm-column');
        if (!col) return;
        board.querySelectorAll('.crm-column').forEach(c => c.classList.remove('drag-over'));
        col.classList.add('drag-over');
    });

    board.addEventListener('dragleave', e => {
        const col = e.target.closest('.crm-column');
        if (col && !col.contains(e.relatedTarget)) col.classList.remove('drag-over');
    });

    board.addEventListener('drop', async e => {
        e.preventDefault();
        const col = e.target.closest('.crm-column');
        col?.classList.remove('drag-over');
        if (!col || !draggedEl || !draggedId) return;

        const newStage = col.dataset.stage;
        const cardsEl = col.querySelector('.crm-cards');
        cardsEl.querySelector('.crm-empty-col')?.remove();
        cardsEl.prepend(draggedEl);
        updateCounters();

        try {
            const res = await fetch('/crm/' + draggedId + '/stage', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ stage: newStage }),
            });
            if (!res.ok) throw new Error('server error');
        } catch {
            window.location.reload();
        }
    });

    function updateCounters() {
        board.querySelectorAll('.crm-column').forEach(col => {
            const count = col.querySelectorAll('[data-lead-id]').length;
            const countEl = col.querySelector('[data-col-count]');
            if (countEl) countEl.textContent = count + ' lead';
            const badge = col.querySelector('.badge');
            if (badge) badge.textContent = count;
        });
    }
})();
</script>
@endif
@endpush
