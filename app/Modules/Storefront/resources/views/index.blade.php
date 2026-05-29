@extends('layouts.admin')

@section('title', 'Storefront')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Commerce</div>
                <h2 class="page-title">Storefront</h2>
                <p class="text-muted mb-0">Pantau order online, payment issue, backlog fulfillment, dan alur pengiriman dari satu workspace.</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="{{ route('storefront.public.index') }}" class="btn btn-outline-secondary">Buka Toko Publik</a>
                <a href="{{ route('sales.commerce.index') }}" class="btn btn-primary">Lihat Semua Order</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Orders</div>
                    <div class="h2 mb-1">{{ number_format((int) $metrics['orders']) }}</div>
                    <div class="text-muted small">{{ number_format((int) $metrics['paid_orders']) }} order sudah dibayar</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Payments Received</div>
                    <div class="h2 mb-1">Rp{{ number_format((float) $metrics['payments_received'], 0, ',', '.') }}</div>
                    <div class="text-muted small">Revenue terkonfirmasi Rp{{ number_format((float) $metrics['gross_revenue'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Butuh Tindakan</div>
                    <div class="h2 mb-1">{{ number_format((int) $metrics['ready_fulfillment']) }}</div>
                    <div class="text-muted small">{{ number_format((int) $metrics['shipping_queue']) }} order siap kirim &middot; {{ number_format((int) $metrics['quote_issues']) }} quote issue</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Payment Issue</div>
                    <div class="h2 mb-1">{{ number_format((int) $metrics['pending_orders']) }}</div>
                    <div class="text-muted small">{{ number_format((int) $metrics['expired_orders']) }} order expired &middot; {{ number_format((int) $metrics['shipped_today']) }} shipped today</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="fw-semibold mb-1">Kesiapan Shipping Storefront</div>
                    <div class="text-muted small">Pantau kesehatan konfigurasi delivery agar checkout publik tidak berhenti di tengah jalan.</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge {{ $health['public_storefront_enabled'] ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }}">
                        {{ $health['public_storefront_enabled'] ? 'Storefront aktif' : 'Storefront nonaktif' }}
                    </span>
                    <span class="badge {{ $health['origin_ready'] ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }}">
                        {{ $health['origin_ready'] ? 'Origin siap' : 'Origin belum lengkap' }}
                    </span>
                    <span class="badge {{ $health['provider_ready'] ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow' }}">
                        {{ $health['provider_ready'] ? ($health['provider_label'] ?: 'Provider siap') : 'Provider belum siap' }}
                    </span>
                    <span class="badge {{ $health['missing_weight_products'] === 0 ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow' }}">
                        {{ number_format((int) $health['missing_weight_products']) }} produk fisik tanpa berat
                    </span>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <div class="text-muted small">Company publik</div>
                    <div class="fw-medium">{{ $health['public_company_name'] ?: 'Belum dipilih' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Produk fisik aktif</div>
                    <div class="fw-medium">{{ number_format((int) $health['shippable_products']) }}</div>
                </div>
                <div class="col-md-4 d-flex gap-2 align-items-end">
                    <a href="{{ route('settings.index', ['tab' => 'general']) }}" class="btn btn-sm btn-outline-secondary">Cek Settings</a>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-primary">Cek Products</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Order Terbaru</h3>
                        <div class="text-muted small mt-1">Ringkasan alur order publik yang paling baru masuk.</div>
                    </div>
                    <a href="{{ route('payments.commerce.index') }}" class="btn btn-sm btn-outline-secondary">Payment Status</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                @php($shippingTotal = (float) data_get($order->totals_snapshot, 'shipping_total', data_get($order->meta, 'commerce.shipping.amount', 0)))
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.commerce.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->sale_number }}</a>
                                        <div class="text-muted small">{{ optional($order->transaction_date)->format('d M Y H:i') }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $order->customer_name_snapshot ?: '-' }}</div>
                                        <div class="text-muted small">{{ $order->customer_phone_snapshot ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div>@include('shared.commerce.status-chip', ['type' => 'commerce', 'value' => data_get($order->meta, 'commerce.status', 'pending_payment')])</div>
                                        <div class="mt-1">@include('shared.commerce.status-chip', ['type' => 'fulfillment', 'value' => data_get($order->meta, 'commerce.fulfillment.status', 'pending')])</div>
                                    </td>
                                    <td>
                                        <div>Rp{{ number_format((float) $order->grand_total, 0, ',', '.') }}</div>
                                        @if($shippingTotal > 0)
                                            <div class="text-muted small">Ongkir Rp{{ number_format($shippingTotal, 0, ',', '.') }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada order terbaru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h3 class="card-title mb-0">Quick Links</h3>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('shipping.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>Shipping Queue</span>
                        <span class="badge bg-azure-lt text-azure">{{ number_format((int) $metrics['shipping_queue']) }}</span>
                    </a>
                    <a href="{{ route('fulfillment.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>Fulfillment Queue</span>
                        <span class="badge bg-orange-lt text-orange">{{ number_format((int) $metrics['ready_fulfillment']) }}</span>
                    </a>
                    <a href="{{ route('payments.commerce.index') }}" class="list-group-item list-group-item-action">Payment Status</a>
                    <a href="{{ route('storefront.public.index') }}" class="list-group-item list-group-item-action">Tinjau Halaman Publik</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header"><h3 class="card-title mb-0">Perlu Konfirmasi Pembayaran</h3></div>
                <div class="list-group list-group-flush">
                    @forelse($paymentIssueOrders as $order)
                        <a href="{{ route('sales.commerce.show', $order) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $order->sale_number }}</div>
                                    <div class="text-muted small">{{ $order->customer_name_snapshot ?: '-' }}</div>
                                </div>
                                @include('shared.commerce.status-chip', ['type' => 'commerce', 'value' => data_get($order->meta, 'commerce.status', 'pending_payment')])
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">Tidak ada payment issue yang perlu ditindak.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header"><h3 class="card-title mb-0">Backlog Fulfillment</h3></div>
                <div class="list-group list-group-flush">
                    @forelse($fulfillmentBacklog as $order)
                        <a href="{{ route('sales.commerce.show', $order) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $order->sale_number }}</div>
                                    <div class="text-muted small">{{ strtoupper((string) data_get($order->meta, 'commerce.fulfillment_method', 'pickup')) }}</div>
                                </div>
                                @include('shared.commerce.status-chip', ['type' => 'fulfillment', 'value' => data_get($order->meta, 'commerce.fulfillment.status', 'pending')])
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">Belum ada backlog fulfillment.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header"><h3 class="card-title mb-0">Backlog Shipping</h3></div>
                <div class="list-group list-group-flush">
                    @forelse($shippingBacklog as $order)
                        <a href="{{ route('shipping.index') }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $order->sale_number }}</div>
                                    <div class="text-muted small">{{ $order->customer_name_snapshot ?: '-' }}</div>
                                </div>
                                @include('shared.commerce.status-chip', ['type' => 'shipping', 'value' => data_get($order->meta, 'commerce.shipping.status', 'pending')])
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">Belum ada order delivery yang menunggu pengiriman.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
