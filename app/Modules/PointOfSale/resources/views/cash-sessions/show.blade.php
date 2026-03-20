@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $shift->code }}</h2>
        <div class="text-muted small">{{ $shift->cashier ? $shift->cashier->name : '-' }} | Branch {{ $shift->branch_id ?: '-' }}</div>
    </div>
    <a href="{{ route('pos.shifts.index') }}" class="btn btn-outline-secondary">Kembali</a>
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
                <div class="mb-2"><div class="text-muted small">Status</div><div><span class="badge {{ $shift->isActive() ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">{{ $shift->status }}</span></div></div>
                <div class="mb-2"><div class="text-muted small">Opening Cash</div><div>Rp {{ number_format((float) $shift->opening_cash_amount, 0, ',', '.') }}</div></div>
                <div class="mb-2"><div class="text-muted small">Opened At</div><div>{{ $shift->opened_at ? $shift->opened_at->format('d/m/Y H:i') : '-' }}</div></div>
                <div class="mb-2"><div class="text-muted small">Total Transaksi</div><div>{{ $saleCount }}</div></div>
                <div class="mb-2"><div class="text-muted small">Total Sales</div><div>Rp {{ number_format($salesTotal, 0, ',', '.') }}</div></div>
                <div class="mb-2"><div class="text-muted small">Total Cash Payment</div><div>Rp {{ number_format($cashPaymentTotal, 0, ',', '.') }}</div></div>
                <div class="mb-2"><div class="text-muted small">Expected Cash</div><div>Rp {{ number_format($expectedCash, 0, ',', '.') }}</div></div>
                <div class="mb-2"><div class="text-muted small">Closing Cash</div><div>{{ $shift->closing_cash_amount === null ? '-' : 'Rp ' . number_format((float) $shift->closing_cash_amount, 0, ',', '.') }}</div></div>
                <div><div class="text-muted small">Difference</div><div>{{ $shift->difference_amount === null ? '-' : 'Rp ' . number_format((float) $shift->difference_amount, 0, ',', '.') }}</div></div>
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
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-primary">Simpan Movement</button>
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
                            <label class="form-label">Closing Cash</label>
                            <input type="number" step="0.01" min="0" name="closing_cash_amount" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Closing Note</label>
                            <textarea name="closing_note" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Close Shift</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Sales</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Sale</th><th>Status</th><th>Total</th><th>Waktu</th></tr></thead>
                    <tbody>
                        @forelse($shift->sales as $sale)
                            <tr>
                                <td>{{ $sale->sale_number }}</td>
                                <td>{{ $sale->status }}</td>
                                <td>Rp {{ number_format((float) $sale->grand_total, 0, ',', '.') }}</td>
                                <td>{{ $sale->transaction_date ? $sale->transaction_date->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Belum ada sale pada shift ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Payments</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Payment</th><th>Method</th><th>Amount</th><th>Paid At</th></tr></thead>
                    <tbody>
                        @forelse($shift->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_number }}</td>
                                <td>{{ $payment->method ? $payment->method->name : '-' }}</td>
                                <td>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                <td>{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Belum ada payment pada shift ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Cash Movements</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Type</th><th>Amount</th><th>Notes</th><th>Waktu</th></tr></thead>
                    <tbody>
                        @forelse($shift->cashMovements as $movement)
                            <tr>
                                <td>{{ $movement->movement_type }}</td>
                                <td>Rp {{ number_format((float) $movement->amount, 0, ',', '.') }}</td>
                                <td>{{ $movement->notes ?: '-' }}</td>
                                <td>{{ $movement->occurred_at ? $movement->occurred_at->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Belum ada cash in/out.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
