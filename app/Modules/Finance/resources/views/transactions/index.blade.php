@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Finance Transactions</h2>
        <div class="text-muted small">Kas masuk, kas keluar, dan pengeluaran operasional.</div>
        <div class="text-muted small">Company: {{ $company?->name ?? '-' }}</div>
    </div>
    <a href="{{ route('finance.transactions.create') }}" class="btn btn-primary">Create Transaction</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Cash In</div>
                <div class="fs-2 fw-bold text-success">Rp {{ number_format((float) $summary['cash_in_total'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Cash Out + Expense</div>
                <div class="fs-2 fw-bold text-danger">Rp {{ number_format((float) $summary['cash_out_total'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Net Cash Flow</div>
                <div class="fs-2 fw-bold {{ $summary['net_cash_flow'] >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format((float) $summary['net_cash_flow'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="transaction_type" class="form-select">
                    <option value="">All</option>
                    <option value="cash_in" @selected(($filters['transaction_type'] ?? '') === 'cash_in')>Cash In</option>
                    <option value="cash_out" @selected(($filters['transaction_type'] ?? '') === 'cash_out')>Cash Out</option>
                    <option value="expense" @selected(($filters['transaction_type'] ?? '') === 'expense')>Expense</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="finance_category_id" class="form-select">
                    <option value="">All</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['finance_category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">User</label>
                <select name="created_by" class="form-select">
                    <option value="">All</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) ($filters['created_by'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Branch</label>
                <input type="number" min="1" name="branch_id" class="form-control" value="{{ $filters['branch_id'] ?? '' }}" placeholder="Optional">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-outline-primary">Filter</button>
                <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Number</th><th>Date</th><th>Type</th><th>Category</th><th>Amount</th><th>User</th><th>Branch</th>@if($shiftEnabled)<th>Shift</th>@endif<th></th></tr></thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_number }}</td>
                        <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : '-' }}</td>
                        <td>{{ $transaction->transaction_type }}</td>
                        <td>{{ $transaction->category ? $transaction->category->name : '-' }}</td>
                        <td>Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</td>
                        <td>{{ $transaction->creator ? $transaction->creator->name : '-' }}</td>
                        <td>{{ $transaction->branch_id ?: '-' }}</td>
                        @if($shiftEnabled)
                            <td>{{ $transaction->shift ? $transaction->shift->code : '-' }}</td>
                        @endif
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('finance.transactions.show', $transaction) }}" class="btn btn-outline-secondary btn-sm">Detail</a>
                                <a href="{{ route('finance.transactions.edit', $transaction) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('finance.transactions.destroy', $transaction) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Hapus transaksi ini?">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $shiftEnabled ? '9' : '8' }}" class="text-center text-muted">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $transactions->links() }}</div>
</div>
@endsection
