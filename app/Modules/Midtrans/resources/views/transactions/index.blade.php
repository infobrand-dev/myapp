@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Midtrans Transactions</h2>
        <div class="text-muted small">Riwayat transaksi pembayaran online via Midtrans.</div>
    </div>
    <a href="{{ route('midtrans.settings.edit') }}" class="btn btn-outline-secondary">Settings</a>
</div>

@if(!$isConfigured)
    <div class="alert alert-warning">
        Midtrans belum dikonfigurasi atau tidak aktif. <a href="{{ route('midtrans.settings.edit') }}">Atur sekarang →</a>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Total Transaksi</div>
            <div class="h2 mb-0">{{ $summary['total'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Settlement</div>
            <div class="h2 mb-0 text-green">{{ $summary['settled'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Pending</div>
            <div class="h2 mb-0 text-yellow">{{ $summary['pending'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Gagal/Expired</div>
            <div class="h2 mb-0 text-red">{{ $summary['failed'] }}</div>
        </div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Cari</label>
                <input type="text" name="search" class="form-control" placeholder="Order ID, Transaksi ID, nama, email..."
                       value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['pending' => 'Pending', 'settlement' => 'Settlement', 'capture' => 'Capture', 'deny' => 'Deny', 'cancel' => 'Cancel', 'expire' => 'Expire', 'refund' => 'Refund'] as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Payment Type</label>
                <select name="payment_type" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', 'qris' => 'QRIS', 'credit_card' => 'Kartu Kredit', 'bank_transfer' => 'Transfer Bank', 'akulaku' => 'Akulaku'] as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['payment_type'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Dari</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Sampai</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-lg-1 col-md-6 d-grid">
                <button class="btn btn-primary" type="submit">Filter</button>
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
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Payment Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr>
                        <td class="text-nowrap text-muted small">{{ $tx->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <div class="font-monospace small">{{ $tx->order_id }}</div>
                            @if($tx->transaction_id)
                                <div class="text-muted small">{{ $tx->transaction_id }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $tx->customer_name ?: '-' }}</div>
                            @if($tx->customer_email)
                                <div class="text-muted small">{{ $tx->customer_email }}</div>
                            @endif
                        </td>
                        <td>
                            @if($tx->payment_type)
                                <span class="badge bg-azure-lt text-azure">{{ $tx->paymentTypeLabel() }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">
                            Rp {{ number_format($tx->gross_amount, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge {{ $tx->statusBadgeClass() }}">
                                {{ ucfirst($tx->transaction_status) }}
                            </span>
                        </td>
                        <td>
                            @if($tx->payment_id)
                                <a href="{{ route('payments.show', $tx->payment_id) }}" class="small">
                                    PAY #{{ $tx->payment_id }}
                                </a>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('midtrans.transactions.show', $tx) }}"
                               class="btn btn-sm btn-outline-secondary btn-icon" title="Detail">
                                <i class="ti ti-eye icon"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted text-center">Belum ada transaksi Midtrans.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
