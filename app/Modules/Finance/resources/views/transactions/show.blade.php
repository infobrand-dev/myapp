@extends('layouts.admin')

@section('title', 'Detail Transaksi')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">{{ $transaction->transaction_number }}</h2>
            <p class="text-muted mb-0">{{ $transaction->transaction_type }} &mdash; {{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : '-' }}</p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
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
            <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Header</h3></div>
            <div class="card-body">
                <div class="mb-2"><div class="text-muted small">Type</div><div>{{ $transaction->transaction_type }}</div></div>
                <div class="mb-2"><div class="text-muted small">Amount</div><div>{{ $money->format((float) $transaction->amount, $currency) }}</div></div>
                <div class="mb-2"><div class="text-muted small">Category</div><div>{{ $transaction->category ? $transaction->category->name : '-' }}</div></div>
                <div class="mb-2"><div class="text-muted small">User</div><div>{{ $transaction->creator ? $transaction->creator->name : '-' }}</div></div>
                <div class="mb-2"><div class="text-muted small">Branch</div><div>{{ $transaction->branch_id ?: '-' }}</div></div>
                @if($shiftEnabled)
                    <div class="mb-2"><div class="text-muted small">Shift</div><div>{{ $transaction->shift ? $transaction->shift->code : '-' }}</div></div>
                @endif
                <div><div class="text-muted small">Notes</div><div>{{ $transaction->notes ?: '-' }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Audit Note</h3></div>
            <div class="card-body">
                <p class="mb-0 text-muted">Transaksi ini bersifat operasional.</p>
            </div>
        </div>
    </div>
</div>

@endsection
