@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Receive Goods {{ $purchase->purchase_number }}</h2>
    <div class="text-muted small">Catat penerimaan barang dari supplier.</div>
</div>

<form method="POST" action="{{ route('purchases.receipts.store', $purchase) }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Receipt Header</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Inventory Location</label>
                        <select name="inventory_location_id" class="form-select" required>
                            <option value="">Pilih lokasi</option>
                            @foreach($inventoryLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Receipt Date</label>
                        <input type="datetime-local" name="receipt_date" class="form-control" value="{{ now()->format('Y-m-d\\TH:i') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Receipt Items</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Item</th><th>Ordered</th><th>Received</th><th>Remaining</th><th>Receive Now</th></tr></thead>
                        <tbody>
                            @foreach($purchase->items as $item)
                                @php($remaining = max(0, (float) $item->qty - (float) $item->qty_received))
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                                        <div class="text-muted small">{{ $item->variant_name_snapshot ?: '-' }}</div>
                                        <input type="hidden" name="items[{{ $loop->index }}][purchase_item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) $item->qty_received, 2, ',', '.') }}</td>
                                    <td>{{ number_format($remaining, 2, ',', '.') }}</td>
                                    <td><input type="number" min="0" max="{{ $remaining }}" step="0.0001" name="items[{{ $loop->index }}][qty_received]" class="form-control" value="{{ $remaining > 0 ? $remaining : 0 }}"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <div class="text-muted small">Qty tidak boleh melebihi sisa purchase.</div>
                    <button type="submit" class="btn btn-success">Post Receipt</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
