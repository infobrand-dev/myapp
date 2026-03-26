@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $purchase->purchase_number }}</h2>
        <div class="text-muted small">{{ optional($purchase->purchase_date)->format('d M Y H:i') ?? '-' }} | Supplier: {{ $purchase->supplier_name_snapshot ?: '-' }}</div>
    </div>
    <div class="btn-list">
        @if($purchase->status === 'draft')
            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline-secondary">Edit Draft</a>
            <form method="POST" action="{{ route('purchases.finalize', $purchase) }}">@csrf<button type="submit" class="btn btn-primary">Finalize</button></form>
        @endif
        @if(in_array($purchase->status, ['confirmed', 'partial_received']))
            <a href="{{ route('purchases.receive', $purchase) }}" class="btn btn-outline-success">Receive Goods</a>
        @endif
        <a href="{{ route('purchases.print', $purchase) }}" class="btn btn-outline-primary">Print</a>
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Purchase Items</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Item</th><th>Qty</th><th>Received</th><th>Cost</th><th>Total</th></tr></thead>
                    <tbody>
                    @foreach($purchase->items as $item)
                        <tr>
                            <td><div class="fw-semibold">{{ $item->product_name_snapshot }}</div><div class="text-muted small">{{ $item->variant_name_snapshot ?: '-' }} | SKU: {{ $item->sku_snapshot ?: '-' }}</div></td>
                            <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->qty_received, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $item->unit_cost, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Goods Receipts</h3></div>
            <div class="card-body">
                @forelse($purchase->receipts as $receipt)
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <div><div class="fw-semibold">{{ $receipt->receipt_number }}</div><div class="text-muted small">{{ optional($receipt->receipt_date)->format('d M Y H:i') ?? '-' }}</div></div>
                            <div class="text-end"><div>{{ optional($receipt->inventoryLocation)->name ?: '-' }}</div><div class="text-muted small">Qty: {{ number_format((float) $receipt->total_received_qty, 2, ',', '.') }}</div></div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Belum ada receiving.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Header</h3></div>
            <div class="card-body">
                <div class="mb-3"><div class="text-muted small">Supplier</div><div>{{ $purchase->supplier_name_snapshot ?: '-' }}</div><div class="text-muted small">{{ $purchase->supplier_phone_snapshot ?: '-' }}</div></div>
                <div class="mb-3"><div class="text-muted small">Status</div><div>{{ $statusOptions[$purchase->status] ?? ucfirst($purchase->status) }}</div><div class="text-muted small">Payment: {{ $paymentStatusOptions[$purchase->payment_status] ?? ucfirst($purchase->payment_status) }}</div></div>
                <div class="mb-3"><div class="text-muted small">Supplier Ref</div><div>{{ $purchase->supplier_reference ?: '-' }}</div><div class="text-muted small">Invoice: {{ $purchase->supplier_invoice_number ?: '-' }}</div></div>
                <div class="mb-3"><div class="text-muted small">Totals</div><div>Subtotal: Rp {{ number_format((float) $purchase->subtotal, 0, ',', '.') }}</div><div>Discount: Rp {{ number_format((float) $purchase->discount_total, 0, ',', '.') }}</div><div>Tax: Rp {{ number_format((float) $purchase->tax_total, 0, ',', '.') }}</div><div class="fw-semibold">Grand: Rp {{ number_format((float) $purchase->grand_total, 0, ',', '.') }}</div><div>Paid: Rp {{ number_format((float) $purchase->paid_total, 0, ',', '.') }}</div><div>Balance: Rp {{ number_format((float) $purchase->balance_due, 0, ',', '.') }}</div></div>
                <div class="mb-3"><div class="text-muted small">Notes</div><div>{{ $purchase->notes ?: '-' }}</div></div>

                @if($purchase->status === 'draft')
                    <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" class="mb-3">@csrf<label class="form-label">Cancel Reason</label><textarea name="reason" class="form-control" rows="2"></textarea><button type="submit" class="btn btn-outline-warning w-100 mt-2">Cancel Draft</button></form>
                @endif
                @if(in_array($purchase->status, ['confirmed', 'partial_received', 'received']))
                    <form method="POST" action="{{ route('purchases.void', $purchase) }}">@csrf<label class="form-label">Void Reason</label><textarea name="reason" class="form-control" rows="3" required></textarea><button type="submit" class="btn btn-danger w-100 mt-2">Void Purchase</button></form>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Payments</h3></div>
            <div class="card-body">
                @if($purchase->paymentAllocations->isNotEmpty())
                    @foreach($purchase->paymentAllocations as $allocation)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold">{{ optional(optional($allocation->payment)->method)->name ?: '-' }}</div>
                            <div class="text-muted small">{{ optional(optional($allocation->payment)->paid_at)->format('d M Y H:i') ?: '-' }}</div>
                            <div>Allocated: Rp {{ number_format((float) $allocation->amount, 0, ',', '.') }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted mb-3">Belum ada payment tercatat.</div>
                @endif
                @if($purchase->status !== 'draft' && Route::has('payments.create'))
                    <a href="{{ route('payments.create', ['purchase_id' => $purchase->id]) }}" class="btn btn-outline-primary w-100">Tambah Pembayaran</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
