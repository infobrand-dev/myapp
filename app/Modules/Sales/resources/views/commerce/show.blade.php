@extends('layouts.admin')

@section('title', 'Commerce Order Detail')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Commerce</div>
                <h2 class="page-title">{{ $sale->sale_number }}</h2>
                <p class="text-muted mb-0">Ringkasan order publik, pembayaran, dan fulfillment.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('sales.commerce.index') }}" class="btn btn-outline-secondary">Kembali ke Orders</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Status</h3></div>
                <div class="card-body">
                    @php($shippingTotal = (float) data_get($sale->totals_snapshot, 'shipping_total', data_get($sale->meta, 'commerce.shipping.amount', 0)))
                    <div class="mb-2"><strong>Status Commerce:</strong> @include('shared.commerce.status-chip', ['type' => 'commerce', 'value' => data_get($sale->meta, 'commerce.status', $sale->status)])</div>
                    <div class="mb-2"><strong>Status Bayar:</strong> @include('shared.commerce.status-chip', ['type' => 'payment', 'value' => $sale->payment_status])</div>
                    <div class="mb-2"><strong>Status Checkout:</strong> @include('shared.commerce.status-chip', ['type' => 'payment_state', 'value' => data_get($sale->meta, 'commerce.payment.status', 'pending')])</div>
                    <div class="mb-2"><strong>Fulfillment:</strong> @include('shared.commerce.status-chip', ['type' => 'fulfillment', 'value' => data_get($sale->meta, 'commerce.fulfillment.status', 'pending')])</div>
                    <div class="mb-2"><strong>Shipping:</strong> @include('shared.commerce.status-chip', ['type' => 'shipping', 'value' => data_get($sale->meta, 'commerce.shipping.status', 'pending')])</div>
                    <div class="mb-2"><strong>Source:</strong> {{ ucfirst((string) $sale->source) }}</div>
                    <div class="mb-2"><strong>Subtotal Produk:</strong> {{ number_format((float) $sale->subtotal, 0, ',', '.') }}</div>
                    @if($shippingTotal > 0)
                        <div class="mb-2"><strong>Ongkir:</strong> {{ number_format($shippingTotal, 0, ',', '.') }}</div>
                    @endif
                    <div class="mb-2"><strong>Total:</strong> {{ number_format((float) $sale->grand_total, 0, ',', '.') }}</div>
                    <div><strong>Sisa Tagihan:</strong> {{ number_format((float) $sale->balance_due, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Item Order</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sale->items as $item)
                                <tr>
                                    <td>{{ $item->product_name_snapshot ?: optional($item->product)->name ?: '-' }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }}</td>
                                    <td>{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                    <td>{{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada item order.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @php($timeline = collect(data_get($sale->meta, 'commerce.timeline', [])))
            @if($timeline->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header"><h3 class="card-title mb-0">Timeline Order</h3></div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            @foreach($timeline as $entry)
                                @php
                                    $event = (string) data_get($entry, 'event', 'updated');
                                    $label = match ($event) {
                                        'pending_payment' => 'Menunggu pembayaran',
                                        'payment_checkout_created' => 'Checkout pembayaran dibuat',
                                        'paid' => 'Pembayaran diterima',
                                        'shipping_rate_selected' => 'Layanan pengiriman dipilih',
                                        'ready_for_fulfillment' => 'Siap diproses',
                                        'packing' => 'Packing dimulai',
                                        'shipped' => 'Pesanan dikirim',
                                        'expired' => 'Pesanan kedaluwarsa',
                                        'payment_cancelled' => 'Pembayaran dibatalkan',
                                        'cancelled' => 'Pesanan dibatalkan',
                                        'payment_failed' => 'Pembayaran belum berhasil',
                                        default => str_replace('_', ' ', ucfirst($event)),
                                    };
                                @endphp
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold">{{ $label }}</div>
                                        @if(data_get($entry, 'context.note'))
                                            <div class="text-muted small">{{ data_get($entry, 'context.note') }}</div>
                                        @elseif(data_get($entry, 'context.tracking_number'))
                                            <div class="text-muted small">Resi: {{ data_get($entry, 'context.tracking_number') }}</div>
                                        @endif
                                    </div>
                                    <div class="text-muted small text-nowrap">{{ \Illuminate\Support\Carbon::parse((string) data_get($entry, 'at'))->format('d M Y H:i') }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
