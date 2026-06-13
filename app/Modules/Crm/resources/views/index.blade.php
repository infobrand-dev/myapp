@extends('layouts.tenant')

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

@include('crm::partials.nav')

<div class="d-lg-none sticky-top mb-3" style="top: calc(var(--tblr-navbar-height, 0px) + .75rem); z-index: 20;">
    <div class="card border-0 shadow-sm">
        <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between gap-2">
            <div class="min-width-0">
                <div class="text-muted text-uppercase fw-semibold" style="font-size:.68rem;">CRM Workspace</div>
                <div class="fw-semibold" style="font-size:.9rem;">{{ $summary['active_deals'] }} deal aktif • {{ $summary['follow_up_due_today'] }} follow-up hari ini</div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#crmFilterDrawer" aria-controls="crmFilterDrawer">
                    <i class="ti ti-adjustments-horizontal me-1"></i>Filter
                </button>
                <a href="{{ route('crm.create') }}" class="btn btn-dark btn-sm">
                    <i class="ti ti-plus me-1"></i>Lead
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-primary-lt text-primary rounded-3"><i class="ti ti-users fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Total Leads</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $summary['total_leads'] }}</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-azure-lt text-azure rounded-3"><i class="ti ti-address-book fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">CRM Contacts</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $summary['total_contacts'] }}</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-green-lt text-green rounded-3"><i class="ti ti-briefcase fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Active Deals</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $summary['active_deals'] }}</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-orange-lt text-orange rounded-3"><i class="ti ti-coin fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Pipeline Value</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $money->format((float) $summary['pipeline_value']) }}</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-indigo-lt text-indigo rounded-3"><i class="ti ti-target-arrow fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Conversion Rate</div><div class="fs-3 fw-bold lh-1 mt-1">{{ number_format($summary['conversion_rate'], 1) }}%</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-cyan-lt text-cyan rounded-3"><i class="ti ti-calendar-due fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Follow-Up Due Today</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $summary['follow_up_due_today'] }}</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-red-lt text-red rounded-3"><i class="ti ti-alert-circle fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Overdue Follow-Up</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $summary['overdue_follow_up'] }}</div></div></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar bg-lime-lt text-lime rounded-3"><i class="ti ti-trophy fs-4"></i></span><div><div class="text-muted small fw-semibold text-uppercase">Won Deals</div><div class="fs-3 fw-bold lh-1 mt-1">{{ $summary['won'] }}</div></div></div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Top Sales</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead>
                        <tr>
                            <th>Owner</th>
                            <th class="text-end">Leads</th>
                            <th class="text-end">Pipeline</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topSales as $row)
                            <tr>
                                <td>{{ $row->owner?->name ?? 'Unassigned' }}</td>
                                <td class="text-end">{{ (int) $row->total_leads }}</td>
                                <td class="text-end">{{ $money->format((float) $row->pipeline_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data owner.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Lead Source Performance</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th class="text-end">Leads</th>
                            <th class="text-end">Won</th>
                            <th class="text-end">CVR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sourcePerformance as $row)
                            <tr>
                                <td>{{ \App\Modules\Crm\Support\CrmSourceCatalog::options()[$row['lead_source']] ?? $row['lead_source'] }}</td>
                                <td class="text-end">{{ $row['total_leads'] }}</td>
                                <td class="text-end">{{ $row['won_leads'] }}</td>
                                <td class="text-end">{{ number_format($row['conversion_rate'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada source performance.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 d-none d-lg-block">
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
                @can('crm.export')
                    <a href="{{ route('crm.export', request()->query()) }}" class="btn btn-outline-secondary" title="Export CSV"><i class="ti ti-download"></i></a>
                @endcan
            </div>
        </form>
    </div>
</div>

<div class="offcanvas offcanvas-bottom h-auto d-lg-none" tabindex="-1" id="crmFilterDrawer" aria-labelledby="crmFilterDrawerLabel">
    <div class="offcanvas-header">
        <div>
            <div class="text-muted text-uppercase fw-semibold" style="font-size:.7rem;">CRM Filters</div>
            <h3 class="offcanvas-title mb-0" id="crmFilterDrawerLabel">Leads / Deals</h3>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form method="GET" action="{{ route('crm.index') }}" class="row g-3">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="col-12">
                <label class="form-label">Cari</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-search text-muted"></i></span>
                    <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Judul, contact, email, atau catatan">
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Stage</label>
                <select name="stage" class="form-select">
                    <option value="">Semua stage</option>
                    @foreach($stageOptions as $key => $label)
                        <option value="{{ $key }}" @selected($filters['stage'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Owner</label>
                <select name="owner_user_id" class="form-select">
                    <option value="">Semua owner</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((int) $filters['owner_user_id'] === (int) $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-check mb-0">
                    <input type="checkbox" name="show_archived" value="1" class="form-check-input" @checked($filters['show_archived'])>
                    <span class="form-check-label">Tampilkan arsip</span>
                </label>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit"><i class="ti ti-filter me-1"></i>Terapkan</button>
                <a href="{{ route('crm.index', ['view' => $viewMode]) }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
                @can('crm.export')
                    <a href="{{ route('crm.export', request()->query()) }}" class="btn btn-outline-secondary"><i class="ti ti-download"></i></a>
                @endcan
            </div>
        </form>
    </div>
</div>

@if($viewMode === 'kanban')
<div class="crm-kanban-wrap">
    <div class="crm-board" id="crm-kanban-board" data-stage-url-template="{{ route('crm.stage', ['lead' => '__LEAD__']) }}">
        @foreach($board as $column)
            @php
                $colValue = (float) $column['items']->sum('estimated_value');
            @endphp
            <div class="crm-column" data-stage="{{ $column['key'] }}" data-stage-label="{{ $column['label'] }}">
                <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                    <div class="min-width-0">
                        <div class="fw-semibold" style="font-size:.85rem;">{{ $column['label'] }}</div>
                        <div class="text-muted" style="font-size:.72rem;" data-col-count>
                            <span data-col-count-number>{{ $column['items']->count() }}</span> lead
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
                                    <form method="POST" action="{{ route('crm.stage', $lead) }}" class="mt-2 d-md-none">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <select name="stage" class="form-select">
                                                @foreach($stageOptions as $moveKey => $moveLabel)
                                                    <option value="{{ $moveKey }}" @selected($lead->stage === $moveKey)>{{ $moveLabel }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline-secondary">Move</button>
                                        </div>
                                    </form>
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
                                <div class="small text-muted">{{ \App\Modules\Crm\Support\CrmSourceCatalog::options()[$lead->lead_source] ?? $lead->lead_source }}</div>
                            @endif
                            @if($lead->qualification_status)
                                <div class="small text-muted">{{ $lead->qualification_status }}@if($lead->lead_score !== null) · Score {{ $lead->lead_score }}@endif</div>
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
                            @if($lead->expected_close_date)
                                <div class="small text-muted">Close {{ $lead->expected_close_date->translatedFormat('d M Y') }}</div>
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
document.addEventListener('DOMContentLoaded', function () {
    const board = document.getElementById('crm-kanban-board');
    if (!board || !window.Sortable) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const urlTemplate = board.dataset.stageUrlTemplate || '/crm/leads/__LEAD__/stage';

    function ensureEmptyState(column) {
        const cards = column.querySelector('.crm-cards');
        if (!cards) return;

        const hasCards = cards.querySelector('[data-lead-id]');
        let emptyState = cards.querySelector('.crm-empty-col');

        if (!hasCards && !emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'text-center text-muted py-4 crm-empty-col';
            emptyState.style.fontSize = '.8rem';
            emptyState.style.border = '2px dashed var(--tblr-card-border-color)';
            emptyState.style.borderRadius = '.75rem';
            emptyState.innerHTML = '<i class="ti ti-inbox d-block mb-1" style="font-size:1.4rem;"></i>Belum ada lead';
            cards.appendChild(emptyState);
        }

        if (hasCards && emptyState) {
            emptyState.remove();
        }
    }

    function formatCurrency(total) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(total);
    }

    function updateCounters() {
        board.querySelectorAll('.crm-column').forEach(column => {
            const cards = Array.from(column.querySelectorAll('[data-lead-id]'));
            const count = cards.length;
            const total = cards.reduce((sum, card) => sum + Number(card.dataset.value || 0), 0);
            const countNumber = column.querySelector('[data-col-count-number]');
            const badge = column.querySelector('.badge');
            let valueEl = column.querySelector('[data-col-value]');

            if (countNumber) countNumber.textContent = count;
            if (badge) badge.textContent = count;

            if (total > 0) {
                if (!valueEl) {
                    valueEl = document.createElement('span');
                    valueEl.setAttribute('data-col-value', '');
                    column.querySelector('[data-col-count]')?.appendChild(document.createTextNode(' '));
                    column.querySelector('[data-col-count]')?.appendChild(valueEl);
                }

                valueEl.textContent = '• ' + formatCurrency(total);
            } else if (valueEl) {
                valueEl.remove();
            }

            ensureEmptyState(column);
        });
    }

    async function persistStage(card, column) {
        const leadId = card.dataset.leadId;
        const stage = column.dataset.stage;
        const previousStage = card.dataset.stage || '';
        card.dataset.stage = stage;

        try {
            const response = await fetch(urlTemplate.replace('__LEAD__', leadId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ stage }),
            });

            if (!response.ok) {
                throw new Error('crm stage update failed');
            }
        } catch (_) {
            card.dataset.stage = previousStage;
            window.location.reload();
        }
    }

    board.querySelectorAll('.crm-column').forEach(column => {
        const cards = column.querySelector('.crm-cards');
        if (!cards) return;

        window.Sortable.create(cards, {
            group: 'crm-kanban',
            animation: 180,
            delayOnTouchOnly: true,
            delay: 80,
            ghostClass: 'drag-over',
            chosenClass: 'dragging',
            dragClass: 'dragging',
            onAdd: function (event) {
                updateCounters();
                persistStage(event.item, column);
            },
            onUpdate: updateCounters,
            onRemove: updateCounters,
            onStart: function () {
                column.classList.add('drag-over');
            },
            onEnd: function () {
                board.querySelectorAll('.crm-column').forEach(col => col.classList.remove('drag-over'));
                updateCounters();
            },
        });

        ensureEmptyState(column);
    });

    updateCounters();
});
</script>
@endif
@endpush

