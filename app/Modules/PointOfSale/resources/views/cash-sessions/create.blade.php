@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Open Shift</h2>
    <div class="text-muted small">Buka sesi kasir baru dengan opening cash dan branch yang digunakan.</div>
</div>

@if($activeSession)
    <div class="alert alert-warning">
        Anda masih memiliki shift aktif:
        <a href="{{ route('pos.shifts.show', $activeSession) }}">{{ $activeSession->code }}</a>
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

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('pos.shifts.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Opening Cash</label>
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
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" {{ $activeSession ? 'disabled' : '' }}>Open Shift</button>
                <a href="{{ route('pos.shifts.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
