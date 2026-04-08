@extends('layouts.admin')

@section('title', 'Buat Transaksi')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Buat Transaksi</h2>
            <p class="text-muted mb-0">Pencatatan kas masuk dan keluar. Company: {{ $company?->name ?? '-' }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
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

<form method="POST" action="{{ route('finance.transactions.store') }}">
    @csrf

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Transaksi</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="transaction_type" class="form-select @error('transaction_type') is-invalid @enderror" required>
                        @foreach($transactionTypeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('transaction_type', 'cash_out') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('transaction_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account <span class="text-danger">*</span></label>
                    <select name="finance_account_id" class="form-select @error('finance_account_id') is-invalid @enderror" required>
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected((string) old('finance_account_id', optional($accounts->firstWhere('is_default', true))->id) === (string) $account->id)>{{ $account->name }} ({{ \App\Modules\Finance\Models\FinanceAccount::typeOptions()[$account->account_type] ?? $account->account_type }})</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Gunakan `cash` untuk kas tunai dan `bank` untuk transaksi rekening.</div>
                    @error('finance_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="finance_category_id" class="form-select @error('finance_category_id') is-invalid @enderror" required>
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('finance_category_id') === (string) $category->id)>{{ $category->name }} ({{ $category->transaction_type }})</option>
                        @endforeach
                    </select>
                    @error('finance_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <input type="number" min="1" name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" value="{{ old('branch_id', old('outlet_id')) }}" placeholder="Optional">
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Shift Reference</label>
                    <select name="pos_cash_session_id" class="form-select @error('pos_cash_session_id') is-invalid @enderror">
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
                    @error('pos_cash_session_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>

@endsection
