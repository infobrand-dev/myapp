@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $allStages = \App\Modules\Crm\Support\CrmStageCatalog::options();
    $stageKeys = array_keys($allStages);
    $currentIdx = array_search($lead->stage, $stageKeys, true);
    $priorityBadge = \App\Modules\Crm\Support\CrmStageCatalog::priorityBadgeClass($lead->priority);
    $isOverdue = $lead->next_follow_up_at && $lead->next_follow_up_at->isPast();
@endphp

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3">
        <div class="min-width-0">
            <div class="page-pretitle">CRM</div>
            <h2 class="page-title d-flex align-items-center gap-2 flex-wrap">
                {{ $lead->title }}
                <span class="badge {{ \App\Modules\Crm\Support\CrmStageCatalog::badgeClass($lead->stage) }}">
                    {{ $allStages[$lead->stage] ?? $lead->stage }}
                </span>
                @if($lead->is_archived)
                <span class="badge bg-secondary-lt text-secondary">Arsip</span>
                @endif
            </h2>
        </div>
        <div class="btn-list flex-shrink-0">
            <a href="{{ route('crm.edit', $lead) }}" class="btn btn-outline-secondary">
                <i class="ti ti-pencil me-1"></i>Edit
            </a>
            <a href="{{ route('crm.index', ['view' => 'kanban']) }}" class="btn btn-outline-secondary">
                <i class="ti ti-layout-kanban me-1"></i>Kanban
            </a>
            <a href="{{ route('crm.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

{{-- Stage pipeline stepper --}}
<div class="card mb-4">
    <div class="card-body py-2 px-3">
        <div class="d-flex" style="gap:0;">
            @foreach($allStages as $stageKey => $stageLabel)
            @php
                $idx = array_search($stageKey, $stageKeys, true);
                if ($lead->stage === 'won') {
                    $stepClass = $stageKey === 'won' ? 'active-won' : ($stageKey === 'lost' ? '' : 'done');
                } elseif ($lead->stage === 'lost') {
                    $stepClass = $stageKey === 'lost' ? 'active-lost' : 'done';
                } else {
                    if ($idx < $currentIdx)       $stepClass = 'done';
                    elseif ($idx === $currentIdx)  $stepClass = 'active';
                    else                           $stepClass = '';
                }
                $isCurrentStep = $lead->stage === $stageKey;
            @endphp
            <form method="POST" action="{{ route('crm.stage', $lead) }}" class="flex-fill">
                @csrf
                <input type="hidden" name="stage" value="{{ $stageKey }}">
                <button type="submit"
                        class="crm-pipeline-step w-100 {{ $stepClass }}"
                        title="{{ $isCurrentStep ? 'Stage saat ini' : 'Pindah ke ' . $stageLabel }}"
                        @disabled($isCurrentStep)
                        data-loading="Memindahkan...">
                    {{ $stageLabel }}
                </button>
            </form>
            @endforeach
        </div>
    </div>
</div>

{{-- Metrics --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Estimated Value</div>
                <div class="fs-3 fw-bold text-primary">
                    {{ $money->format((float) ($lead->estimated_value ?? 0), $lead->currency) }}
                </div>
                @if($lead->currency && $lead->currency !== 'IDR')
                <div class="small text-muted">{{ $lead->currency }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Probability</div>
                <div class="fs-3 fw-bold">{{ $lead->probability ?? 0 }}%</div>
                @if($lead->probability)
                <div class="progress mt-2" style="height:4px;">
                    <div class="progress-bar bg-primary" style="width:{{ $lead->probability }}%"></div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Next Follow Up</div>
                @if($lead->next_follow_up_at)
                    <div class="fw-bold {{ $isOverdue ? 'text-danger' : '' }}">
                        {{ $lead->next_follow_up_at->translatedFormat('d M Y') }}
                    </div>
                    <div class="small {{ $isOverdue ? 'text-danger' : 'text-muted' }}">
                        {{ $lead->next_follow_up_at->translatedFormat('H:i') }}
                        @if($isOverdue)
                        <span class="badge bg-red-lt text-red ms-1">Overdue</span>
                        @endif
                    </div>
                @else
                    <div class="text-muted">Belum dijadwalkan</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Priority</div>
                <span class="badge {{ $priorityBadge }} px-3 py-2" style="font-size:.8rem;">
                    <i class="ti ti-flag me-1"></i>{{ \Illuminate\Support\Str::headline($lead->priority) }}
                </span>
                @if($lead->lead_source)
                <div class="small text-muted mt-2">
                    <i class="ti ti-source-code me-1"></i>{{ $lead->lead_source }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Notes --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="ti ti-notes me-2 text-muted"></i>Catatan CRM
                </h3>
                <a href="{{ route('crm.edit', $lead) }}" class="btn btn-sm btn-ghost-secondary">
                    <i class="ti ti-pencil me-1"></i>Edit
                </a>
            </div>
            <div class="card-body">
                @if($lead->notes)
                    <div style="white-space:pre-line; line-height:1.7;">{{ $lead->notes }}</div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="ti ti-notes d-block mb-2" style="font-size:2rem; opacity:.3;"></i>
                        Belum ada catatan.
                        <a href="{{ route('crm.edit', $lead) }}">Tambahkan catatan</a>.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Detail sidebar --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="ti ti-info-circle me-2 text-muted"></i>Detail Lead
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted ps-3" style="width:42%">Contact</td>
                            <td>
                                @if($lead->contact)
                                    <a href="{{ route('contacts.show', $lead->contact) }}">{{ $lead->contact->name }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @if($lead->contact?->email)
                        <tr>
                            <td class="text-muted ps-3">Email</td>
                            <td><a href="mailto:{{ $lead->contact->email }}" class="text-truncate d-block" style="max-width:130px;">{{ $lead->contact->email }}</a></td>
                        </tr>
                        @endif
                        @if($lead->contact?->mobile || $lead->contact?->phone)
                        <tr>
                            <td class="text-muted ps-3">Phone</td>
                            <td>{{ $lead->contact->mobile ?: $lead->contact->phone }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted ps-3">Owner</td>
                            <td>{{ $lead->owner?->name ?? '-' }}</td>
                        </tr>
                        @if($lead->company)
                        <tr>
                            <td class="text-muted ps-3">Company</td>
                            <td>{{ $lead->company->name }}</td>
                        </tr>
                        @endif
                        @if($lead->branch)
                        <tr>
                            <td class="text-muted ps-3">Branch</td>
                            <td>{{ $lead->branch->name }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted ps-3">Dibuat</td>
                            <td>{{ $lead->created_at->translatedFormat('d M Y') }}</td>
                        </tr>
                        @if($lead->last_contacted_at)
                        <tr>
                            <td class="text-muted ps-3">Last Contact</td>
                            <td>{{ $lead->last_contacted_at->translatedFormat('d M Y') }}</td>
                        </tr>
                        @endif
                        @if($lead->won_at)
                        <tr>
                            <td class="text-muted ps-3">Won At</td>
                            <td class="text-success fw-semibold">
                                <i class="ti ti-trophy me-1"></i>{{ $lead->won_at->translatedFormat('d M Y') }}
                            </td>
                        </tr>
                        @endif
                        @if($lead->lost_at)
                        <tr>
                            <td class="text-muted ps-3">Lost At</td>
                            <td class="text-danger fw-semibold">{{ $lead->lost_at->translatedFormat('d M Y') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted ps-3">Status</td>
                            <td>
                                @if($lead->is_archived)
                                    <span class="badge bg-secondary-lt text-secondary">Diarsipkan</span>
                                @else
                                    <span class="badge bg-success-lt text-success">Aktif</span>
                                @endif
                            </td>
                        </tr>
                        @if(!empty($lead->labels))
                        <tr>
                            <td class="text-muted ps-3">Labels</td>
                            <td>
                                @foreach($lead->labels as $label)
                                <span class="badge bg-blue-lt text-blue me-1 mb-1">{{ $label }}</span>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('crm.destroy', $lead) }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger w-100"
                            data-confirm="Hapus lead CRM &quot;{{ $lead->title }}&quot;? Tindakan ini tidak bisa dibatalkan."
                            data-loading="Menghapus...">
                        <i class="ti ti-trash me-1"></i>Hapus Lead
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
