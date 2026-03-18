@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Return Note {{ $saleReturn->return_number }}</h2>
        <div class="text-muted small">Print view untuk dokumen sales return. Sale asli tetap direferensikan tanpa diubah.</div>
    </div>
    <div class="btn-list">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="{{ route('sales.returns.show', $saleReturn) }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card invoice-print">
    <div class="card-body">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="fw-bold">MyApp</div>
                <div class="text-muted small">Sales return note</div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="fw-semibold">{{ $saleReturn->return_number }}</div>
                <div class="text-muted small">Sale: {{ $saleReturn->sale_number_snapshot }}</div>
                <div class="text-muted small">{{ optional($saleReturn->return_date)->format('d M Y H:i') ?? '-' }}</div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="text-muted small">Customer</div>
                <div>{{ $saleReturn->customer_name_snapshot ?: 'Guest / Walk-in' }}</div>
                <div class="text-muted small">{{ $saleReturn->customer_phone_snapshot ?: '-' }}</div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="text-muted small">Status</div>
                <div>{{ ucfirst($saleReturn->status) }}</div>
                <div class="text-muted small">Reason</div>
                <div>{{ $saleReturn->reason }}</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty Return</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($saleReturn->items as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot }}{{ $item->variant_name_snapshot ? ' - ' . $item->variant_name_snapshot : '' }}</td>
                            <td>{{ number_format((float) $item->qty_returned, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="3" class="text-end fw-bold">Grand Return</td><td class="fw-bold">Rp {{ number_format((float) $saleReturn->grand_total, 0, ',', '.') }}</td></tr>
                </tfoot>
            </table>
        </div>
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
