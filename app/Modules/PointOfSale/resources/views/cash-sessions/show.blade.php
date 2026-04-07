@extends('layouts.admin')

@section('title', 'Detail Shift')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $defaultCurrency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Point of Sale · Cash Session</div>
            <h2 class="page-title">{{ $shift->code }}</h2>
            <p class="text-muted mb-0">{{ $shift->cashier ? $shift->cashier->name : '-' }} | Branch {{ $shift->branch_id ?: '-' }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('pos.shifts.index') }}" class="btn btn-outline-secondary">
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

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Shift Summary</h3></div>
            <div class="card-body">
                <div class="mb-2"><div class="text-muted small">Status</div><div><span class="badge {{ $shift->isActive() ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">{{ $shift->status }}</span></div></div>
                <div class="mb-2"><div class="text-muted small">Opening Cash</div><div>{{ $money->format((float) $shift->opening_cash_amount, $shift->currency_code ?: $defaultCurrency) }}</div></div>
                <div class="mb-2"><div class="text-muted small">Opened At</div><div>{{ $shift->opened_at ? $shift->opened_at->format('d/m/Y H:i') : '-' }}</div></div>
                <div class="mb-2"><div class="text-muted small">Total Transaksi</div><div>{{ $saleCount }}</div></div>
                <div class="mb-2"><div class="text-muted small">Total Sales</div><div>{{ $money->format($salesTotal, $defaultCurrency) }}</div></div>
                <div class="mb-2"><div class="text-muted small">Total Cash Payment</div><div>{{ $money->format($cashPaymentTotal, $defaultCurrency) }}</div></div>
                <div class="mb-2"><div class="text-muted small">Expected Cash</div><div>{{ $money->format($expectedCash, $defaultCurrency) }}</div></div>
                <div class="mb-2"><div class="text-muted small">Closing Cash</div><div>{{ $shift->closing_cash_amount === null ? '-' : $money->format((float) $shift->closing_cash_amount, $shift->currency_code ?: $defaultCurrency) }}</div></div>
                <div><div class="text-muted small">Difference</div><div>{{ $shift->difference_amount === null ? '-' : $money->format((float) $shift->difference_amount, $shift->currency_code ?: $defaultCurrency) }}</div></div>
            </div>
        </div>

        @if($shift->isActive() && auth()->user() && auth()->user()->can('pos.record-cash-movement'))
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Cash In / Out</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('pos.shifts.movements.store', $shift) }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Type</label>
                            <select name="movement_type" class="form-select">
                                <option value="cash_in">Cash In</option>
                                <option value="cash_out">Cash Out</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-primary w-100">Simpan Movement</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if($shift->isActive() && auth()->user() && auth()->user()->can('pos.close-shift'))
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Close Shift</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('pos.shifts.close', $shift) }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Closing Cash <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="closing_cash_amount" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Closing Note</label>
                            <textarea name="closing_note" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100">
                                <i class="ti ti-lock me-1"></i>Close Shift
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Sales</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Sale</th><th>Status</th><th>Total</th><th>Waktu</th></tr></thead>
                        <tbody>
                            @forelse($shift->sales as $sale)
                                <tr>
                                    <td>{{ $sale->sale_number }}</td>
                                    <td>{{ $sale->status }}</td>
                                    <td>{{ $money->format((float) $sale->grand_total, $sale->currency_code) }}</td>
                                    <td>{{ $sale->transaction_date ? $sale->transaction_date->format('d/m/Y H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada sale pada shift ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Payments</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Payment</th><th>Method</th><th>Amount</th><th>Paid At</th></tr></thead>
                        <tbody>
                            @forelse($shift->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_number }}</td>
                                    <td>{{ $payment->method ? $payment->method->name : '-' }}</td>
                                    <td>{{ $money->format((float) $payment->amount, $payment->currency_code) }}</td>
                                    <td>{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada payment pada shift ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Cash Movements</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Type</th><th>Amount</th><th>Notes</th><th>Waktu</th></tr></thead>
                        <tbody>
                            @forelse($shift->cashMovements as $movement)
                                <tr>
                                    <td>{{ $movement->movement_type }}</td>
                                    <td>{{ $money->format((float) $movement->amount, $shift->currency_code ?: $defaultCurrency) }}</td>
                                    <td>{{ $movement->notes ?: '-' }}</td>
                                    <td>{{ $movement->occurred_at ? $movement->occurred_at->format('d/m/Y H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada cash in/out.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
