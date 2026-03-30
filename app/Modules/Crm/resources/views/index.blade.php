@extends('layouts.admin')

@section('content')
@push('styles')
<style>
    .crm-board {
        display: grid;
        grid-template-columns: repeat(6, minmax(250px, 1fr));
        gap: 1rem;
        align-items: start;
    }
    .crm-column {
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        padding: 1rem;
        min-height: 420px;
    }
    .crm-card {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: .9rem;
        padding: .9rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }
    .crm-card + .crm-card {
        margin-top: .8rem;
    }
    .crm-card-title {
        font-weight: 700;
        color: #0f172a;
    }
    .crm-metric {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        background: #fff;
        height: 100%;
    }
    @media (max-width: 1399.98px) {
        .crm-board {
            grid-template-columns: repeat(3, minmax(240px, 1fr));
        }
    }
    @media (max-width: 991.98px) {
        .crm-board {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="mb-1">CRM</h2>
        <div class="text-muted small">Kelola lead, customer relationship, dan follow up tim dalam bentuk list atau pipeline kanban.</div>
    </div>
    <div class="btn-list">
        <a href="{{ route('crm.index', array_merge(request()->query(), ['view' => 'list'])) }}" class="btn {{ $viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary' }}">List</a>
        <a href="{{ route('crm.index', array_merge(request()->query(), ['view' => 'kanban'])) }}" class="btn {{ $viewMode === 'kanban' ? 'btn-primary' : 'btn-outline-secondary' }}">Kanban</a>
        <a href="{{ route('crm.create') }}" class="btn btn-dark">Tambah Lead</a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="crm-metric">
            <div class="text-muted text-uppercase small fw-bold mb-1">Total Lead</div>
            <div class="fs-2 fw-bold">{{ $summary['total'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="crm-metric">
            <div class="text-muted text-uppercase small fw-bold mb-1">Open Pipeline</div>
            <div class="fs-2 fw-bold">{{ $summary['open'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="crm-metric">
            <div class="text-muted text-uppercase small fw-bold mb-1">Won</div>
            <div class="fs-2 fw-bold">{{ $summary['won'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="crm-metric">
            <div class="text-muted text-uppercase small fw-bold mb-1">Estimated Value</div>
            <div class="fs-2 fw-bold">Rp {{ number_format((float) $summary['value'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('crm.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="col-lg-4">
                <label class="form-label">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Judul lead, nama contact, email, atau catatan">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Stage</label>
                <select name="stage" class="form-select">
                    <option value="">Semua stage</option>
                    @foreach($stageOptions as $stageKey => $stageLabel)
                        <option value="{{ $stageKey }}" @selected($filters['stage'] === $stageKey)>{{ $stageLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Owner</label>
                <select name="owner_user_id" class="form-select">
                    <option value="">Semua owner</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((int) $filters['owner_user_id'] === (int) $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-check">
                    <input type="checkbox" name="show_archived" value="1" class="form-check-input" @checked($filters['show_archived'])>
                    <span class="form-check-label">Tampilkan arsip</span>
                </label>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Terapkan Filter</button>
                <a href="{{ route('crm.index', ['view' => $viewMode]) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if($viewMode === 'kanban')
    <div class="crm-board">
        @foreach($board as $column)
            <div class="crm-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold">{{ $column['label'] }}</div>
                        <div class="small text-muted">{{ $column['items']->count() }} lead</div>
                    </div>
                    <span class="badge {{ $column['badge_class'] }}">{{ $column['label'] }}</span>
                </div>

                @forelse($column['items'] as $lead)
                    <div class="crm-card">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <a href="{{ route('crm.show', $lead) }}" class="crm-card-title text-decoration-none">{{ $lead->title }}</a>
                            <span class="badge bg-secondary-lt text-secondary">{{ \Illuminate\Support\Str::headline($lead->priority) }}</span>
                        </div>
                        <div class="small text-muted mb-2">
                            {{ $lead->contact?->name ?? 'Tanpa contact terhubung' }}
                        </div>
                        <div class="small mb-2">
                            <i class="ti ti-currency-rupiah me-1"></i>Rp {{ number_format((float) ($lead->estimated_value ?? 0), 0, ',', '.') }}
                        </div>
                        <div class="small text-muted mb-3">
                            <div><i class="ti ti-user me-1"></i>{{ $lead->owner?->name ?? 'Belum ada owner' }}</div>
                            <div><i class="ti ti-calendar-event me-1"></i>{{ optional($lead->next_follow_up_at)->translatedFormat('d M Y H:i') ?? 'Belum ada follow up' }}</div>
                        </div>
                        <div class="d-flex gap-2">
                            @if($prev = \App\Modules\Crm\Support\CrmStageCatalog::previousStage($lead->stage))
                                <form method="POST" action="{{ route('crm.stage', $lead) }}">
                                    @csrf
                                    <input type="hidden" name="stage" value="{{ $prev }}">
                                    <button class="btn btn-sm btn-outline-secondary" title="Pindah ke {{ $stageOptions[$prev] }}">
                                        <i class="ti ti-arrow-left"></i>
                                    </button>
                                </form>
                            @endif
                            @if($next = \App\Modules\Crm\Support\CrmStageCatalog::nextStage($lead->stage))
                                <form method="POST" action="{{ route('crm.stage', $lead) }}">
                                    @csrf
                                    <input type="hidden" name="stage" value="{{ $next }}">
                                    <button class="btn btn-sm btn-outline-primary" title="Pindah ke {{ $stageOptions[$next] }}">
                                        <i class="ti ti-arrow-right"></i>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('crm.edit', $lead) }}" class="btn btn-sm btn-outline-dark ms-auto">Edit</a>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada lead pada stage ini.</div>
                @endforelse
            </div>
        @endforeach
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Contact</th>
                        <th>Stage</th>
                        <th>Owner</th>
                        <th>Value</th>
                        <th>Follow Up</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td>
                                <a href="{{ route('crm.show', $lead) }}" class="text-decoration-none fw-semibold">{{ $lead->title }}</a>
                                <div class="small text-muted">{{ $lead->lead_source ?: 'Sumber belum diisi' }}</div>
                            </td>
                            <td>{{ $lead->contact?->name ?? '-' }}</td>
                            <td><span class="badge {{ \App\Modules\Crm\Support\CrmStageCatalog::badgeClass($lead->stage) }}">{{ $stageOptions[$lead->stage] ?? $lead->stage }}</span></td>
                            <td>{{ $lead->owner?->name ?? '-' }}</td>
                            <td>Rp {{ number_format((float) ($lead->estimated_value ?? 0), 0, ',', '.') }}</td>
                            <td>{{ optional($lead->next_follow_up_at)->translatedFormat('d M Y H:i') ?? '-' }}</td>
                            <td class="text-end">
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('crm.edit', $lead) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <a href="{{ route('crm.show', $lead) }}" class="btn btn-sm btn-primary">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada lead CRM.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $leads?->links() }}
        </div>
    </div>
@endif
@endsection
