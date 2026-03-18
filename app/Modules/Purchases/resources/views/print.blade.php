@extends('layouts.admin')

@section('content')
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
                        <td>Rp {{ number_format((float) $item->unit_cost, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="text-end">
            <div>Subtotal: Rp {{ number_format((float) $purchase->subtotal, 0, ',', '.') }}</div>
            <div>Discount: Rp {{ number_format((float) $purchase->discount_total, 0, ',', '.') }}</div>
            <div>Tax: Rp {{ number_format((float) $purchase->tax_total, 0, ',', '.') }}</div>
            <div class="fw-semibold">Grand Total: Rp {{ number_format((float) $purchase->grand_total, 0, ',', '.') }}</div>
        </div>
    </div>
</div>
@endsection
