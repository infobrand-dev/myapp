@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Reports Dashboard</h2>
        <div class="text-muted small">Ringkasan data dari semua modul.</div>
    </div>
</div>

@include('reports::partials.nav')

<div class="card mb-3">
    <div class="card-body">
        <div class="alert alert-info mb-3">Semua card mengikuti active company/branch context dari topbar switcher. Saat branch tidak dipilih, report branch-aware hanya membaca data company-level dengan <code>branch_id = null</code>.</div>
        <form method="GET" class="row g-3">
            <div class="col-md-4"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-4"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-4 d-flex align-items-end gap-2"><button class="btn btn-primary w-100">Apply</button><a href="{{ route('reports.dashboard') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach($cards as $card)
        <div class="col-md-6 col-xl-4">
            <a href="{{ $card['route'] }}" class="card text-decoration-none text-reset h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $card['title'] }}</div>
                    <div class="fs-2 fw-bold mt-1">{{ $card['value'] }}</div>
                    <div class="text-muted small mt-2">{{ $card['meta'] }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3">
    @foreach($reportCatalog as $group)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">{{ $group['title'] }}</h3>
                    <a href="{{ $group['route'] }}" class="btn btn-sm btn-outline-primary">Open</a>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        @foreach($group['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
