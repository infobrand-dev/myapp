@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Invoice {{ $sale->sale_number }}</h2>
        <div class="text-muted small">Invoice penjualan.</div>
    </div>
    <div class="btn-list">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<div class="card invoice-print">
    <div class="card-body">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <img src="{{ asset('brand/logo-default.png') }}" alt="{{ config('app.name') }}" style="height: 30px; width: auto; display: block; margin-bottom: 0.5rem;">
                <div class="text-muted small">Sales invoice backoffice</div>
                @if(!empty($documentSettings['document_header']))
                    <div class="text-muted small mt-2">{!! nl2br(e($documentSettings['document_header'])) !!}</div>
                @endif
            </div>
            <div class="col-md-6 text-md-end">
                <div class="fw-semibold">{{ $sale->sale_number }}</div>
                <div class="text-muted small">{{ optional($sale->transaction_date)->format('d M Y H:i') ?? '-' }}</div>
                <div class="text-muted small">Source: {{ strtoupper($sale->source) }}</div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="text-muted small">Customer</div>
                <div>{{ $sale->customer_name_snapshot ?? 'Guest / Walk-in' }}</div>
                <div class="text-muted small">{{ $sale->customer_email_snapshot ?: '-' }}</div>
                <div class="text-muted small">{{ $sale->customer_phone_snapshot ?: '-' }}</div>
                <div class="text-muted small">{{ $sale->customer_address_snapshot ?: '-' }}</div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="text-muted small">Payment status</div>
                <div>{{ ucfirst($sale->payment_status) }}</div>
                <div class="text-muted small">Status</div>
                <div>{{ ucfirst($sale->status) }}</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot }}{{ $item->variant_name_snapshot ? ' - ' . $item->variant_name_snapshot : '' }}</td>
                            <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                            <td>{{ $money->format((float) $item->unit_price, $sale->currency_code) }}</td>
                            <td>{{ $money->format((float) $item->discount_total, $sale->currency_code) }}</td>
                            <td>{{ $money->format((float) $item->tax_total, $sale->currency_code) }}</td>
                            <td>{{ $money->format((float) $item->line_total, $sale->currency_code) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="5" class="text-end">Subtotal</td><td>{{ $money->format((float) $sale->subtotal, $sale->currency_code) }}</td></tr>
                    <tr><td colspan="5" class="text-end">Discount</td><td>{{ $money->format((float) $sale->discount_total, $sale->currency_code) }}</td></tr>
                    <tr><td colspan="5" class="text-end">Tax</td><td>{{ $money->format((float) $sale->tax_total, $sale->currency_code) }}</td></tr>
                    <tr><td colspan="5" class="text-end fw-bold">Grand Total</td><td class="fw-bold">{{ $money->format((float) $sale->grand_total, $sale->currency_code) }}</td></tr>
                    <tr><td colspan="5" class="text-end">Paid</td><td>{{ $money->format((float) $sale->paid_total, $sale->currency_code) }}</td></tr>
                    <tr><td colspan="5" class="text-end">Balance Due</td><td>{{ $money->format((float) $sale->balance_due, $sale->currency_code) }}</td></tr>
                </tfoot>
            </table>
        </div>

        @if(!empty($documentSettings['document_footer']))
            <div class="mt-4 text-muted small">{!! nl2br(e($documentSettings['document_footer'])) !!}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<style>
@media print {
    .navbar,
    .navbar-vertical,
    .btn-list,
    .alert {
        display: none !important;
    }
    .page-wrapper {
        margin: 0 !important;
    }
    .invoice-print {
        border: 0 !important;
        box-shadow: none !important;
    }
}
</style>
@endpush
