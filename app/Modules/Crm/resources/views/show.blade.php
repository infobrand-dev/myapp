@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <h2 class="mb-0">{{ $lead->title }}</h2>
            <span class="badge {{ \App\Modules\Crm\Support\CrmStageCatalog::badgeClass($lead->stage) }}">{{ $stageOptions[$lead->stage] ?? $lead->stage }}</span>
        </div>
        <div class="text-muted small">
            {{ $lead->contact?->name ?? 'Tanpa contact terhubung' }}
            | Owner {{ $lead->owner?->name ?? 'belum diassign' }}
            | Priority {{ \Illuminate\Support\Str::headline($lead->priority) }}
        </div>
    </div>
    <div class="btn-list">
        <a href="{{ route('crm.edit', $lead) }}" class="btn btn-outline-secondary">Edit</a>
        <a href="{{ route('crm.index', ['view' => 'kanban']) }}" class="btn btn-outline-secondary">Kanban</a>
        <a href="{{ route('crm.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Estimated Value</div><div class="fs-3 fw-bold">Rp {{ number_format((float) ($lead->estimated_value ?? 0), 0, ',', '.') }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Probability</div><div class="fs-3 fw-bold">{{ $lead->probability ?? 0 }}%</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Next Follow Up</div><div class="fw-bold">{{ optional($lead->next_follow_up_at)->translatedFormat('d M Y H:i') ?? '-' }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Lead Source</div><div class="fw-bold">{{ $lead->lead_source ?: '-' }}</div></div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Catatan CRM</h3>
                <div class="d-flex gap-2">
                    @if($previousStage)
                        <form method="POST" action="{{ route('crm.stage', $lead) }}">
                            @csrf
                            <input type="hidden" name="stage" value="{{ $previousStage }}">
                            <button class="btn btn-sm btn-outline-secondary">Pindah ke {{ $stageOptions[$previousStage] }}</button>
                        </form>
                    @endif
                    @if($nextStage)
                        <form method="POST" action="{{ route('crm.stage', $lead) }}">
                            @csrf
                            <input type="hidden" name="stage" value="{{ $nextStage }}">
                            <button class="btn btn-sm btn-primary">Pindah ke {{ $stageOptions[$nextStage] }}</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="mb-0" style="white-space: pre-line;">{{ $lead->notes ?: 'Belum ada catatan CRM.' }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Lead Detail</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Contact</dt>
                    <dd class="col-sm-7">
                        @if($lead->contact)
                            <a href="{{ route('contacts.show', $lead->contact) }}">{{ $lead->contact->name }}</a>
                        @else
                            -
                        @endif
                    </dd>
                    <dt class="col-sm-5">Email</dt>
                    <dd class="col-sm-7">{{ $lead->contact?->email ?? '-' }}</dd>
                    <dt class="col-sm-5">Phone</dt>
                    <dd class="col-sm-7">{{ $lead->contact?->mobile ?? $lead->contact?->phone ?? '-' }}</dd>
                    <dt class="col-sm-5">Company</dt>
                    <dd class="col-sm-7">{{ $lead->company?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Branch</dt>
                    <dd class="col-sm-7">{{ $lead->branch?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Archived</dt>
                    <dd class="col-sm-7">{{ $lead->is_archived ? 'Yes' : 'No' }}</dd>
                    <dt class="col-sm-5">Labels</dt>
                    <dd class="col-sm-7">
                        @forelse(($lead->labels ?? []) as $label)
                            <span class="badge bg-secondary-lt text-secondary">{{ $label }}</span>
                        @empty
                            -
                        @endforelse
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <form method="POST" action="{{ route('crm.destroy', $lead) }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger w-100" data-confirm="Hapus lead CRM ini?">Hapus Lead</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
