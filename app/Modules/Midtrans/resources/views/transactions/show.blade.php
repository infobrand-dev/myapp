@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Detail Transaksi Midtrans</h2>
        <div class="text-muted small font-monospace">{{ $transaction->order_id }}</div>
    </div>
    <a href="{{ route('midtrans.transactions.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Info Transaksi</h3></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Order ID</div>
                        <div class="font-monospace">{{ $transaction->order_id }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Midtrans Transaction ID</div>
                        <div class="font-monospace">{{ $transaction->transaction_id ?: '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Status</div>
                        <div><span class="badge {{ $transaction->statusBadgeClass() }}">{{ ucfirst($transaction->transaction_status) }}</span></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Fraud Status</div>
                        <div>{{ $transaction->fraud_status ? ucfirst($transaction->fraud_status) : '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Payment Type</div>
                        <div>{{ $transaction->payment_type ? $transaction->paymentTypeLabel() : '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Amount</div>
                        <div class="fw-bold">{{ $money->format((float) $transaction->gross_amount, $transaction->currency_code ?: 'IDR') }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Dibuat</div>
                        <div>{{ $transaction->created_at->format('d M Y H:i:s') }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Settled At</div>
                        <div>{{ $transaction->settled_at ? $transaction->settled_at->format('d M Y H:i:s') : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Customer</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-sm-4">
                        <div class="text-muted small">Nama</div>
                        <div>{{ $transaction->customer_name ?: '-' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Email</div>
                        <div>{{ $transaction->customer_email ?: '-' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Phone</div>
                        <div>{{ $transaction->customer_phone ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($transaction->raw_notification)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Raw Notification</h3>
                </div>
                <div class="card-body">
                    <textarea class="form-control font-monospace small" rows="14" readonly>{{ json_encode($transaction->sanitizedRawNotification(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</textarea>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-5">

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Internal Payment</h3></div>
            <div class="card-body">
                @if($transaction->payment)
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Payment Number</span>
                        <a href="{{ route('payments.show', $transaction->payment) }}">
                            {{ $transaction->payment->payment_number }}
                        </a>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Status</span>
                        <span class="badge text-bg-green">{{ ucfirst($transaction->payment->status) }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Amount</span>
                        <span class="fw-bold">{{ $money->format((float) $transaction->payment->amount, $transaction->payment->currency_code ?: $transaction->currency_code ?: 'IDR') }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Paid At</span>
                        <span>{{ optional($transaction->payment->paid_at)->format('d M Y H:i') }}</span>
                    </div>
                    <a href="{{ route('payments.show', $transaction->payment) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">
                        Lihat Payment Detail
                    </a>
                @elseif($transaction->isSettled())
                    <div class="alert alert-warning mb-0">
                        Transaksi settled tapi Payment record belum dibuat. Periksa log aplikasi.
                    </div>
                @else
                    <div class="text-muted small">Belum ada — dibuat otomatis setelah settlement.</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Linked To</h3></div>
            <div class="card-body">
                @if($transaction->payable_type && $transaction->payable_id)
                    <div class="text-muted small">Type</div>
                    <div class="font-monospace small mb-2">{{ class_basename($transaction->payable_type) }}</div>
                    <div class="text-muted small">ID</div>
                    <div>#{{ $transaction->payable_id }}</div>
                @else
                    <div class="text-muted small">Tidak terhubung ke transaksi tertentu.</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Snap Token</h3></div>
            <div class="card-body">
                @if($transaction->snap_token)
                    <div class="text-muted small mb-1">Token tersimpan, ditampilkan sebagian untuk keamanan.</div>
                    <textarea class="form-control font-monospace small" rows="3" readonly>{{ $transaction->maskedSnapToken() }}</textarea>
                    @if($transaction->snap_redirect_url)
                        <div class="mt-2">
                            <a href="{{ $transaction->snap_redirect_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                Buka Snap URL
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-muted small">Belum ada snap token.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
