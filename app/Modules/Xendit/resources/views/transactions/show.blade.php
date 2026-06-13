@extends('layouts.tenant')

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class); @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Xendit Transaction</h2>
        <div class="text-muted small">{{ $transaction->external_reference }}</div>
    </div>
    <a href="{{ route('xendit.transactions.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="mb-2 d-flex justify-content-between"><span class="text-muted">Status</span><span class="badge {{ $transaction->statusBadgeClass() }}">{{ $transaction->status }}</span></div>
                <div class="mb-2 d-flex justify-content-between"><span class="text-muted">Invoice ID</span><span class="font-monospace">{{ $transaction->invoice_id ?: '-' }}</span></div>
                <div class="mb-2 d-flex justify-content-between"><span class="text-muted">Amount</span><span>{{ $money->format((float) $transaction->gross_amount, $transaction->currency_code ?: 'IDR') }}</span></div>
                <div class="mb-2 d-flex justify-content-between"><span class="text-muted">Customer</span><span>{{ $transaction->customer_name ?: '-' }}</span></div>
                @if($transaction->invoice_url)
                    <div class="mt-3"><a href="{{ $transaction->invoice_url }}" target="_blank" class="btn btn-primary">Buka Invoice</a></div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Webhook Payload</h3></div>
            <div class="card-body">
                <pre class="small mb-0" style="white-space: pre-wrap;">{{ json_encode($transaction->raw_notification ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection

