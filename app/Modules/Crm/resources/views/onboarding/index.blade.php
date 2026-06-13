@extends('layouts.tenant')

@section('content')
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
            <div class="page-pretitle">CRM Setup</div>
            <h2 class="page-title">Onboarding Wizard</h2>
        </div>
        <form method="POST" action="{{ route('crm.onboarding.complete') }}">
            @csrf
            <button class="btn btn-outline-secondary">Lewati & Selesaikan</button>
        </form>
    </div>
</div>

@include('crm::partials.nav')

<div class="row g-3">
    @foreach($wizard['steps'] as $index => $step)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 {{ $step['done'] ? 'border-success' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge {{ $step['done'] ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">Step {{ $index + 1 }}</span>
                        <span class="badge {{ $step['done'] ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' }}">{{ $step['done'] ? 'Done' : 'Pending' }}</span>
                    </div>
                    <div class="fw-semibold mb-2">{{ $step['label'] }}</div>
                    <div class="text-muted small mb-3">{{ $step['hint'] }}</div>
                    @if($step['key'] === 'import_contacts')
                        <a href="{{ route('contacts.index') }}" class="btn btn-outline-primary w-100">Buka Contacts</a>
                    @elseif($step['key'] === 'create_pipeline')
                        <a href="{{ route('crm.pipelines') }}" class="btn btn-outline-primary w-100">Buka Pipelines</a>
                    @elseif($step['key'] === 'add_sales_team')
                        <a href="{{ route('users.index') }}" class="btn btn-outline-primary w-100">Tambah User</a>
                    @elseif($step['key'] === 'create_first_deal')
                        <a href="{{ route('crm.create') }}" class="btn btn-outline-primary w-100">Buat Deal</a>
                    @else
                        <a href="{{ route('crm.follow-ups') }}" class="btn btn-outline-primary w-100">Buka Queue</a>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($wizard['is_complete'])
    <div class="card mt-4"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="fw-semibold">Onboarding CRM selesai</div><div class="small text-muted">Workspace sudah siap dipakai tim sales.</div></div><form method="POST" action="{{ route('crm.onboarding.complete') }}">@csrf<button class="btn btn-primary">Masuk Dashboard CRM</button></form></div></div>
@endif
@endsection

