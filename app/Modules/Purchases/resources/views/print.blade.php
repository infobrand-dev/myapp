@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Print Purchase {{ $purchase->purchase_number }}</h2>
        <div class="text-muted small">{{ $purchase->supplier_name_snapshot ?: '-' }}</div>
    </div>
    <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-6">
                <div class="text-muted small">Purchase Date</div>
                <div>{{ optional($purchase->purchase_date)->format('d M Y H:i') ?? '-' }}</div>
            </div>
            <div class="col-6">
                <div class="text-muted small">Supplier Invoice</div>
                <div>{{ $purchase->supplier_invoice_number ?: '-' }}</div>
            </div>
        </div>
        <table class="table table-vcenter">
            <thead><tr><th>Item</th><th>Qty</th><th>Unit Cost</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($purchase->items as $item)
                    <tr>
                        <td>{{ $item->product_name_snapshot }} {{ $item->variant_name_snapshot ? ' - ' . $item->variant_name_snapshot : '' }}</td>
                        <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                        <td>{{ $money->format((float) $item->unit_cost, $purchase->currency_code) }}</td>
                        <td>{{ $money->format((float) $item->line_total, $purchase->currency_code) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="text-end">
            <div>Subtotal: {{ $money->format((float) $purchase->subtotal, $purchase->currency_code) }}</div>
            <div>Discount: {{ $money->format((float) $purchase->discount_total, $purchase->currency_code) }}</div>
            <div>Tax: {{ $money->format((float) $purchase->tax_total, $purchase->currency_code) }}</div>
            <div class="fw-semibold">Grand Total: {{ $money->format((float) $purchase->grand_total, $purchase->currency_code) }}</div>
        </div>
    </div>
</div>
@endsection
