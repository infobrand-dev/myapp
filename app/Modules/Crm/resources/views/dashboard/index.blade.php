@extends('layouts.tenant')

@section('content')
@php($money = app(\App\Support\MoneyFormatter::class))
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
            <div class="page-pretitle">CRM Suite</div>
            <h2 class="page-title">Dashboard</h2>
        </div>
        <div class="btn-list">
            <a href="{{ route('crm.onboarding') }}" class="btn btn-outline-secondary">Onboarding</a>
            <a href="{{ route('crm.create') }}" class="btn btn-dark">Tambah Deal</a>
        </div>
    </div>
</div>

@include('crm::partials.nav')

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small text-uppercase">Leads</div><div class="fs-2 fw-bold">{{ $summary['leads'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small text-uppercase">Active Deals</div><div class="fs-2 fw-bold">{{ $summary['active_deals'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small text-uppercase">Pipeline Value</div><div class="fs-5 fw-bold">{{ $money->format((float) $summary['pipeline_value']) }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small text-uppercase">Conversion</div><div class="fs-2 fw-bold">{{ number_format($summary['conversion_rate'], 1) }}%</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-warning"><div class="card-body"><div class="text-muted small text-uppercase">Due Today</div><div class="fs-2 fw-bold">{{ $summary['due_today'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-danger"><div class="card-body"><div class="text-muted small text-uppercase">Overdue</div><div class="fs-2 fw-bold">{{ $summary['overdue'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small text-uppercase">Upcoming</div><div class="fs-2 fw-bold">{{ $summary['upcoming'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small text-uppercase">Stale Leads</div><div class="fs-2 fw-bold">{{ $summary['stale_leads'] }}</div></div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Launch Checklist</h3></div>
            <div class="card-body">
                <div class="small text-muted mb-3">Tenant baru harus dapat value dalam kurang dari 5 menit.</div>
                @foreach($wizard['steps'] as $step)
                    <div class="d-flex align-items-start gap-2 {{ !$loop->last ? 'mb-3' : '' }}">
                        <span class="badge {{ $step['done'] ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">{{ $step['done'] ? 'Done' : 'Next' }}</span>
                        <div>
                            <div class="fw-semibold">{{ $step['label'] }}</div>
                            <div class="small text-muted">{{ $step['hint'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Top Sales</h3></div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead><tr><th>Owner</th><th class="text-end">Deals</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                    @forelse($topSales as $row)
                        <tr>
                            <td>{{ $row->owner?->name ?? 'Unassigned' }}</td>
                            <td class="text-end">{{ (int) $row->total_leads }}</td>
                            <td class="text-end">{{ $money->format((float) $row->pipeline_value) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada performa sales.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Follow-Up Queue</h3></div>
            <div class="card-body">
                @forelse($recentFollowUps as $task)
                    <div class="{{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                        <div class="fw-semibold">{{ $task->subject }}</div>
                        <div class="small text-muted">{{ $task->contact?->name ?? $task->lead?->title ?? 'Tanpa relasi' }}</div>
                        <div class="small {{ $task->due_at && $task->due_at->isPast() ? 'text-danger' : 'text-muted' }}">
                            {{ $task->due_at ? $task->due_at->translatedFormat('d M Y H:i') : 'Tanpa jadwal' }}
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada follow-up aktif.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Source Performance</h3></div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead><tr><th>Source</th><th class="text-end">Leads</th><th class="text-end">Won</th></tr></thead>
                    <tbody>
                    @forelse($sourcePerformance as $row)
                        <tr>
                            <td>{{ \App\Modules\Crm\Support\CrmSourceCatalog::options()[$row->lead_source] ?? $row->lead_source }}</td>
                            <td class="text-end">{{ (int) $row->total_leads }}</td>
                            <td class="text-end">{{ (int) $row->won_leads }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data source.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Stage Bottleneck</h3></div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead><tr><th>Stage</th><th>Pipeline</th><th class="text-end">Deals</th></tr></thead>
                    <tbody>
                    @forelse($stageBottlenecks as $stage)
                        <tr>
                            <td>{{ $stage->name }}</td>
                            <td>{{ $stage->pipeline?->name ?? '-' }}</td>
                            <td class="text-end">{{ $stage->leads_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada bottleneck stage.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

