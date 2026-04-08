@extends('layouts.admin')

@section('title', 'Transaksi Keuangan')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Transaksi Keuangan</h2>
            <p class="text-muted mb-0">Kas masuk, kas keluar, dan pengeluaran operasional. Company: {{ $company?->name ?? '-' }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('finance.transactions.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Buat Transaksi
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Cash In</div>
                <div class="fs-2 fw-bold text-success">{{ $money->format((float) $summary['cash_in_total'], $currency) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Cash Out + Expense</div>
                <div class="fs-2 fw-bold text-danger">{{ $money->format((float) $summary['cash_out_total'], $currency) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Net Cash Flow</div>
                <div class="fs-2 fw-bold {{ $summary['net_cash_flow'] >= 0 ? 'text-primary' : 'text-danger' }}">{{ $money->format((float) $summary['net_cash_flow'], $currency) }}</div>
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
                <label class="form-label">Account</label>
                <select name="finance_account_id" class="form-select">
                    <option value="">All</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) ($filters['finance_account_id'] ?? '') === (string) $account->id)>{{ $account->name }}</option>
                    @endforeach
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
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Account</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>User</th>
                        <th>Branch</th>
                        @if($shiftEnabled)<th>Shift</th>@endif
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_number }}</td>
                            <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : '-' }}</td>
                            <td>{{ $transaction->transaction_type }}</td>
                            <td>
                                @if($transaction->account)
                                    {{ $transaction->account->name }}
                                    <div class="text-muted small">{{ \App\Modules\Finance\Models\FinanceAccount::typeOptions()[$transaction->account->account_type] ?? $transaction->account->account_type }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $transaction->category ? $transaction->category->name : '-' }}</td>
                            <td>{{ $money->format((float) $transaction->amount, $currency) }}</td>
                            <td>{{ $transaction->creator ? $transaction->creator->name : '-' }}</td>
                            <td>{{ $transaction->branch_id ?: '-' }}</td>
                            @if($shiftEnabled)
                                <td>{{ $transaction->shift ? $transaction->shift->code : '-' }}</td>
                            @endif
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('finance.transactions.show', $transaction) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.transactions.edit', $transaction) }}" class="btn btn-icon btn-sm btn-outline-primary" title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('finance.transactions.destroy', $transaction) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" title="Hapus" data-confirm="Hapus transaksi ini?">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $shiftEnabled ? '10' : '9' }}" class="text-center py-5">
                                <i class="ti ti-receipt text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada transaksi.</div>
                                <a href="{{ route('finance.transactions.create') }}" class="btn btn-sm btn-primary">Buat Transaksi</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $transactions->links() }}</div>
</div>

@endsection
