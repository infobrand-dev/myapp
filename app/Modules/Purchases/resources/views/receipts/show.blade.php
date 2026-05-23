@extends('layouts.admin')

@section('title', 'Detail Purchase Receipt')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Purchase Receipt</div>
            <h2 class="page-title">{{ $receipt->receipt_number }}</h2>
            <p class="text-muted mb-0">
                {{ optional($receipt->receipt_date)->format('d M Y H:i') ?? '-' }} |
                Purchase:
                <a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->purchase_number }}</a> |
                Supplier: {{ $purchase->supplier_name_snapshot ?: '-' }}
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @include('shared.accounting.mode-badge')
            @if($receipt->inventoryJournal)
                <a href="{{ route('finance.journals.show', $receipt->inventoryJournal) }}" class="btn btn-outline-primary">
                    <i class="ti ti-scale me-1"></i>Lihat Journal
                </a>
            @endif
            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali ke Purchase
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Receipt Items</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Ordered Qty</th>
                                <th>Received Qty</th>
                                <th>Unit Cost</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receipt->items as $item)
                                @php
                                    $purchaseItem = $item->purchaseItem;
                                    $lineTotal = (float) $item->qty_received * (float) ($purchaseItem->unit_cost ?? 0);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $purchaseItem->product_name_snapshot ?: '-' }}</div>
                                        <div class="text-muted small">
                                            {{ $purchaseItem->variant_name_snapshot ?: '-' }} | SKU: {{ $purchaseItem->sku_snapshot ?: '-' }}
                                        </div>
                                    </td>
                                    <td>{{ number_format((float) ($purchaseItem->qty ?? 0), 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) $item->qty_received, 2, ',', '.') }}</td>
                                    <td>{{ $money->format((float) ($purchaseItem->unit_cost ?? 0), $purchase->currency_code) }}</td>
                                    <td>{{ $money->format($lineTotal, $purchase->currency_code) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada item receiving.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Receipt Summary</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Inventory Location</div>
                    <div>{{ optional($receipt->inventoryLocation)->name ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Status</div>
                    <div>{{ ucfirst((string) $receipt->status) }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Total Received Qty</div>
                    <div>{{ number_format((float) $receipt->total_received_qty, 2, ',', '.') }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Receiver</div>
                    <div>{{ optional($receipt->receiver)->name ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Created By</div>
                    <div>{{ optional($receipt->creator)->name ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Notes</div>
                    <div>{{ $receipt->notes ?: '-' }}</div>
                </div>
                @if($receipt->inventoryJournal)
                    <div>
                        <div class="text-muted small">Inventory Journal</div>
                        <div>
                            <a href="{{ route('finance.journals.show', $receipt->inventoryJournal) }}">
                                {{ $receipt->inventoryJournal->journal_number ?: ('Journal #' . $receipt->inventoryJournal->id) }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
