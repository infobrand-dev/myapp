@extends('layouts.admin')

@section('content')
@php
    $salesPayload = $sales->map(function ($sale) {
        return [
            'id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'customer_name' => $sale->customer_name_snapshot ?: 'Guest / Walk-in',
            'transaction_date' => optional($sale->transaction_date)->format('Y-m-d H:i:s'),
            'items' => $sale->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => trim($item->product_name_snapshot . ($item->variant_name_snapshot ? ' - ' . $item->variant_name_snapshot : '')),
                    'sku' => $item->sku_snapshot,
                    'qty' => (float) $item->qty,
                    'line_total' => (float) $item->line_total,
                ];
            })->values()->all(),
        ];
    })->values();
@endphp

<div class="mb-3">
    <h2 class="mb-0">Create Sales Return</h2>
    <div class="text-muted small">Buat retur penjualan baru.</div>
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

<form method="POST" action="{{ route('sales.returns.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Header Return</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Sale Asli</label>
                        <select name="sale_id" class="form-select" id="sale-return-sale-id" required>
                            <option value="">Pilih sale finalized</option>
                            @foreach($sales as $sale)
                                <option value="{{ $sale->id }}" @selected((string) old('sale_id', optional($selectedSale)->id) === (string) $sale->id)>
                                    {{ $sale->sale_number }} | {{ $sale->customer_name_snapshot ?: 'Guest' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Return Date</label>
                        <input type="datetime-local" name="return_date" class="form-control" value="{{ old('return_date', now()->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required>{{ old('reason') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="inventory_restock_required" value="1" @checked(old('inventory_restock_required', true))>
                            <span class="form-check-label">Restock ke Inventory saat finalized</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Inventory Location</label>
                        <select name="inventory_location_id" class="form-select">
                            <option value="">Pilih location</option>
                            @foreach($inventoryLocations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('inventory_location_id') === (string) $location->id)>
                                    {{ $location->name }} ({{ $location->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="refund_required" value="1" @checked(old('refund_required', true))>
                            <span class="form-check-label">Return ini butuh refund via Payments</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Return Items</h3></div>
                <div class="card-body">
                    <div class="text-muted small mb-3">Qty akan divalidasi ulang saat finalisasi.</div>
                    <div class="table-responsive">
                        <table class="table table-vcenter" id="sale-return-items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Sold Qty</th>
                                    <th>Return Qty</th>
                                    <th>Original Total</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <div class="text-muted small">Simpan sebagai draft return.</div>
                    <button type="submit" class="btn btn-primary">Save Draft Return</button>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
(() => {
    const sales = @json($salesPayload);
    const saleSelect = document.getElementById('sale-return-sale-id');
    const tableBody = document.querySelector('#sale-return-items-table tbody');
    const oldItems = @json(old('items', []));

    if (!saleSelect || !tableBody) {
        return;
    }

    const oldItemMap = Object.fromEntries(oldItems.map((item) => [String(item.sale_item_id || ''), item]));

    const renderItems = () => {
        const sale = sales.find((row) => String(row.id) === String(saleSelect.value));
        tableBody.innerHTML = '';

        if (!sale) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Pilih sale untuk menampilkan item.</td></tr>';
            return;
        }

        sale.items.forEach((item, index) => {
            const oldRow = oldItemMap[String(item.id)] || {};
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="fw-semibold">${item.label}</div>
                    <div class="text-muted small">SKU: ${item.sku || '-'}</div>
                    <input type="hidden" name="items[${index}][sale_item_id]" value="${item.id}">
                </td>
                <td>${item.qty.toFixed(2)}</td>
                <td><input type="number" min="0" step="0.0001" class="form-control" name="items[${index}][qty_returned]" value="${oldRow.qty_returned || ''}"></td>
                <td>Rp ${new Intl.NumberFormat('id-ID').format(item.line_total)}</td>
                <td><input type="text" class="form-control" name="items[${index}][notes]" value="${oldRow.notes || ''}"></td>
            `;
            tableBody.appendChild(tr);
        });
    };

    saleSelect.addEventListener('change', renderItems);
    renderItems();
})();
</script>
@endpush
@endsection
