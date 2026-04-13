@extends('layouts.admin')

@section('title', 'Cashbook')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Cashbook</h2>
            <p class="text-muted mb-0">Ledger kas per account dengan opening balance, mutasi, dan saldo berjalan.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali ke transaksi
            </a>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Account</label>
                <select name="finance_account_id" class="form-select">
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) ($filters['finance_account_id'] ?? $selectedAccount?->id) === (string) $account->id)>{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select">
                    <option value="">All</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Tampilkan</button>
                <a href="{{ route('finance.transactions.cashbook') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if($selectedAccount)
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Opening Balance</div>
                    <div class="fs-3 fw-bold">{{ $money->format((float) $openingBalance, $currency) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Closing Balance</div>
                    <div class="fs-3 fw-bold">{{ $money->format((float) $closingBalance, $currency) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Account</div>
                    <div class="fs-4 fw-semibold">{{ $selectedAccount->name }}</div>
                    <div class="text-muted">{{ \App\Modules\Finance\Models\FinanceAccount::typeOptions()[$selectedAccount->account_type] ?? $selectedAccount->account_type }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-vcenter table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Number</th>
                            <th>Description</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Running Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-active">
                            <td colspan="5">Saldo awal</td>
                            <td>{{ $money->format((float) $openingBalance, $currency) }}</td>
                        </tr>
                        @forelse($entries as $entry)
                            <tr>
                                <td>{{ $entry->transaction_date?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('finance.transactions.show', $entry) }}">{{ $entry->transaction_number }}</a>
                                </td>
                                <td>
                                    <div>{{ $entry->category?->name ?? $entry->transaction_type }}</div>
                                    @if($entry->isTransfer())
                                        <div class="text-muted small">Transfer dengan {{ $entry->counterpartyAccount?->name ?? '-' }}</div>
                                    @elseif($entry->notes)
                                        <div class="text-muted small">{{ $entry->notes }}</div>
                                    @endif
                                </td>
                                <td>{{ $entry->cashbook_debit > 0 ? $money->format((float) $entry->cashbook_debit, $currency) : '-' }}</td>
                                <td>{{ $entry->cashbook_credit > 0 ? $money->format((float) $entry->cashbook_credit, $currency) : '-' }}</td>
                                <td>{{ $money->format((float) $entry->cashbook_balance, $currency) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Belum ada mutasi pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-warning mb-0">Belum ada finance account aktif untuk ditampilkan di cashbook.</div>
@endif

@endsection
