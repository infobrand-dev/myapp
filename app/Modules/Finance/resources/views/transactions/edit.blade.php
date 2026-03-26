@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Edit Finance Transaction</h2>
    <div class="text-muted small">{{ $transaction->transaction_number }}</div>
    <div class="text-muted small">Company: {{ $company?->name ?? '-' }}</div>
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
        <form method="POST" action="{{ route('finance.transactions.update', $transaction) }}" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <select name="transaction_type" class="form-select" required>
                    @foreach($transactionTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('transaction_type', $transaction->transaction_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="datetime-local" name="transaction_date" class="form-control"
                    value="{{ old('transaction_date', $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d\TH:i') : '') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control"
                    value="{{ old('amount', $transaction->amount) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Category</label>
                <select name="finance_category_id" class="form-select" required>
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('finance_category_id', $transaction->finance_category_id) === (string) $category->id)>{{ $category->name }} ({{ $category->transaction_type }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Branch</label>
                <input type="number" min="1" name="branch_id" class="form-control"
                    value="{{ old('branch_id', $transaction->branch_id) }}" placeholder="Optional">
            </div>
            <div class="col-md-3">
                <label class="form-label">Shift Reference</label>
                <select name="pos_cash_session_id" class="form-select">
                    <option value="">No shift</option>
                    @if($shiftEnabled)
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}" @selected((string) old('pos_cash_session_id', $transaction->pos_cash_session_id) === (string) $shift->id)>{{ $shift->code }}</option>
                        @endforeach
                    @endif
                </select>
                @if(!$shiftEnabled)
                    <div class="form-hint">POS shift belum tersedia. Field ini tetap opsional.</div>
                @endif
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="4">{{ old('notes', $transaction->notes) }}</textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Update Transaction</button>
                <a href="{{ route('finance.transactions.show', $transaction) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
