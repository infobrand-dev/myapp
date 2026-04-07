@extends('layouts.admin')

@section('title', 'Open Shift')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Point of Sale · Cash Session</div>
            <h2 class="page-title">Open Shift</h2>
            <p class="text-muted mb-0">Buka sesi kasir baru dengan opening cash dan branch yang digunakan.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('pos.shifts.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@if($activeSession)
    <div class="alert alert-warning">
        Anda masih memiliki shift aktif:
        <a href="{{ route('pos.shifts.show', $activeSession) }}"><strong>{{ $activeSession->code }}</strong></a>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('pos.shifts.store') }}">
    @csrf
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detail Shift</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Opening Cash <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="opening_cash_amount" class="form-control" value="{{ old('opening_cash_amount', '0') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Branch</label>
                    <input type="number" min="1" name="branch_id" class="form-control" value="{{ old('branch_id', old('outlet_id')) }}" placeholder="Opsional">
                </div>
                <div class="col-12">
                    <label class="form-label">Opening Note</label>
                    <textarea name="opening_note" class="form-control" rows="4">{{ old('opening_note') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('pos.shifts.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button class="btn btn-primary" {{ $activeSession ? 'disabled' : '' }}>
                <i class="ti ti-player-play me-1"></i>Open Shift
            </button>
        </div>
    </div>
</form>
@endsection
