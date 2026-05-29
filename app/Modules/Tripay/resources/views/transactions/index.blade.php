@extends('layouts.admin')

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class); @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Tripay Transactions</h2>
        <div class="text-muted small">Riwayat hosted checkout via Tripay.</div>
    </div>
    <a href="{{ route('tripay.settings.edit') }}" class="btn btn-outline-secondary">Settings</a>
</div>

@if(!$isConfigured)
    <div class="alert alert-warning">
        Tripay belum dikonfigurasi atau tidak aktif. <a href="{{ route('tripay.settings.edit') }}">Atur sekarang</a>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Total</div><div class="h2 mb-0">{{ $summary['total'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Paid</div><div class="h2 mb-0 text-green">{{ $summary['settled'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Pending</div><div class="h2 mb-0 text-yellow">{{ $summary['pending'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Failed</div><div class="h2 mb-0 text-red">{{ $summary['failed'] }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Cari</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Merchant ref, Tripay ref, customer...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['UNPAID', 'PAID', 'EXPIRED', 'FAILED'] as $status)
                        <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr>
                        <td class="text-muted small text-nowrap">{{ $tx->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <div class="font-monospace small">{{ $tx->merchant_reference }}</div>
                            @if($tx->tripay_reference)
                                <div class="text-muted small">{{ $tx->tripay_reference }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $tx->customer_name ?: '-' }}</div>
                            @if($tx->customer_email)
                                <div class="text-muted small">{{ $tx->customer_email }}</div>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">{{ $money->format((float) $tx->gross_amount, $tx->currency_code ?: 'IDR') }}</td>
                        <td><span class="badge {{ $tx->statusBadgeClass() }}">{{ $tx->status }}</span></td>
                        <td>
                            @if($tx->payment_id)
                                <a href="{{ route('payments.show', $tx->payment_id) }}" class="small">PAY #{{ $tx->payment_id }}</a>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td><a href="{{ route('tripay.transactions.show', $tx) }}" class="btn btn-sm btn-outline-secondary btn-icon"><i class="ti ti-eye icon"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Belum ada transaksi Tripay.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
