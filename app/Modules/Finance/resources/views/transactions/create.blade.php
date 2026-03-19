@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Create Finance Transaction</h2>
    <div class="text-muted small">Pencatatan cash flow operasional ringan, bukan pembukuan debit/credit.</div>
</div>

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
        <form method="POST" action="{{ route('finance.transactions.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <select name="transaction_type" class="form-select" required>
                    @foreach($transactionTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('transaction_type', 'cash_out') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="datetime-local" name="transaction_date" class="form-control" value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Category</label>
                <select name="finance_category_id" class="form-select" required>
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('finance_category_id') === (string) $category->id)>{{ $category->name }} ({{ $category->transaction_type }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Outlet</label>
                <input type="number" min="1" name="outlet_id" class="form-control" value="{{ old('outlet_id') }}" placeholder="Optional">
            </div>
            <div class="col-md-3">
                <label class="form-label">Shift Reference</label>
                <select name="pos_cash_session_id" class="form-select">
                    <option value="">No shift</option>
                    @if($shiftEnabled)
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}" @selected((string) old('pos_cash_session_id') === (string) $shift->id)>{{ $shift->code }}</option>
                        @endforeach
                    @endif
                </select>
                @if(!$shiftEnabled)
                    <div class="form-hint">POS shift belum tersedia. Field ini tetap opsional.</div>
                @endif
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Save Transaction</button>
                <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
