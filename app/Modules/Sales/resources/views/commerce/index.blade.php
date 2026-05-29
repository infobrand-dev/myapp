@extends('layouts.admin')

@section('title', 'Commerce Orders')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Commerce</div>
                <h2 class="page-title">Orders</h2>
                <p class="text-muted mb-0">Pantau order publik, status pembayaran, dan kesiapan operasional dari satu tempat.</p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Orders</div>
                    <div class="h2 mb-1">{{ number_format((int) ($metrics['orders'] ?? 0)) }}</div>
                    <div class="text-muted small">{{ number_format((int) ($metrics['paid'] ?? 0)) }} order sudah dibayar</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Gross Order Value</div>
                    <div class="h2 mb-1">Rp{{ number_format((float) ($metrics['gross'] ?? 0), 0, ',', '.') }}</div>
                    <div class="text-muted small">Termasuk ongkir yang dibayar customer</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Shipping Collected</div>
                    <div class="h2 mb-1">Rp{{ number_format((float) ($metrics['shipping'] ?? 0), 0, ',', '.') }}</div>
                    <div class="text-muted small">{{ number_format((int) ($metrics['ready_to_ship'] ?? 0)) }} order siap dikirim</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Pending Payment</div>
                    <div class="h2 mb-1">{{ number_format((int) ($metrics['pending_payment'] ?? 0)) }}</div>
                    <div class="text-muted small">Perlu follow-up atau retry pembayaran</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-0">Daftar Order</h3>
                <div class="text-muted small mt-1">Gunakan tab cepat untuk pindah dari payment follow-up ke queue delivery atau pickup tanpa mengulang filter dari awal.</div>
            </div>
        </div>
        <div class="card-body border-bottom">
            @php($activeTab = $filters['tab'] ?? 'all')
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('sales.commerce.index', ['tab' => 'all']) }}" class="btn btn-sm {{ $activeTab === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">Semua <span class="badge bg-white text-primary ms-1">{{ number_format((int) ($quickTabs['all'] ?? 0)) }}</span></a>
                <a href="{{ route('sales.commerce.index', ['tab' => 'pending_payment']) }}" class="btn btn-sm {{ $activeTab === 'pending_payment' ? 'btn-primary' : 'btn-outline-secondary' }}">Menunggu Pembayaran <span class="badge bg-white text-primary ms-1">{{ number_format((int) ($quickTabs['pending_payment'] ?? 0)) }}</span></a>
                <a href="{{ route('sales.commerce.index', ['tab' => 'ready_for_fulfillment']) }}" class="btn btn-sm {{ $activeTab === 'ready_for_fulfillment' ? 'btn-primary' : 'btn-outline-secondary' }}">Siap Diproses <span class="badge bg-white text-primary ms-1">{{ number_format((int) ($quickTabs['ready_for_fulfillment'] ?? 0)) }}</span></a>
                <a href="{{ route('sales.commerce.index', ['tab' => 'delivery']) }}" class="btn btn-sm {{ $activeTab === 'delivery' ? 'btn-primary' : 'btn-outline-secondary' }}">Delivery <span class="badge bg-white text-primary ms-1">{{ number_format((int) ($quickTabs['delivery'] ?? 0)) }}</span></a>
                <a href="{{ route('sales.commerce.index', ['tab' => 'pickup']) }}" class="btn btn-sm {{ $activeTab === 'pickup' ? 'btn-primary' : 'btn-outline-secondary' }}">Pickup <span class="badge bg-white text-primary ms-1">{{ number_format((int) ($quickTabs['pickup'] ?? 0)) }}</span></a>
                <a href="{{ route('sales.commerce.index', ['tab' => 'shipped']) }}" class="btn btn-sm {{ $activeTab === 'shipped' ? 'btn-primary' : 'btn-outline-secondary' }}">Sudah Dikirim <span class="badge bg-white text-primary ms-1">{{ number_format((int) ($quickTabs['shipped'] ?? 0)) }}</span></a>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-3">
                <input type="hidden" name="tab" value="{{ $filters['tab'] ?? 'all' }}">
                <div class="col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Order, customer, telepon, produk">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status Commerce</label>
                    <select name="commerce_status" class="form-select">
                        <option value="">Semua</option>
                        <option value="pending_payment" @selected(($filters['commerce_status'] ?? '') === 'pending_payment')>Menunggu pembayaran</option>
                        <option value="paid" @selected(($filters['commerce_status'] ?? '') === 'paid')>Paid</option>
                        <option value="ready_for_fulfillment" @selected(($filters['commerce_status'] ?? '') === 'ready_for_fulfillment')>Siap diproses</option>
                        <option value="expired" @selected(($filters['commerce_status'] ?? '') === 'expired')>Expired</option>
                        <option value="cancelled" @selected(($filters['commerce_status'] ?? '') === 'cancelled')>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pembayaran</label>
                    <select name="payment_state" class="form-select">
                        <option value="">Semua</option>
                        <option value="pending" @selected(($filters['payment_state'] ?? '') === 'pending')>Menunggu pembayaran</option>
                        <option value="checkout_created" @selected(($filters['payment_state'] ?? '') === 'checkout_created')>Checkout dibuat</option>
                        <option value="paid" @selected(($filters['payment_state'] ?? '') === 'paid')>Paid</option>
                        <option value="failed" @selected(($filters['payment_state'] ?? '') === 'failed')>Gagal</option>
                        <option value="expired" @selected(($filters['payment_state'] ?? '') === 'expired')>Expired</option>
                        <option value="cancelled" @selected(($filters['payment_state'] ?? '') === 'cancelled')>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fulfillment</label>
                    <select name="fulfillment_method" class="form-select">
                        <option value="">Semua</option>
                        <option value="pickup" @selected(($filters['fulfillment_method'] ?? '') === 'pickup')>Pickup</option>
                        <option value="delivery" @selected(($filters['fulfillment_method'] ?? '') === 'delivery')>Delivery</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Shipping</label>
                    <select name="shipping_status" class="form-select">
                        <option value="">Semua</option>
                        <option value="pending" @selected(($filters['shipping_status'] ?? '') === 'pending')>Pending</option>
                        <option value="ready" @selected(($filters['shipping_status'] ?? '') === 'ready')>Ready</option>
                        <option value="shipped" @selected(($filters['shipping_status'] ?? '') === 'shipped')>Shipped</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button class="btn btn-primary">Filter</button>
                    <a href="{{ route('sales.commerce.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Status Commerce</th>
                        <th>Pembayaran</th>
                        <th>Ongkir</th>
                        <th>Total</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        @php($shippingTotal = (float) data_get($sale->totals_snapshot, 'shipping_total', data_get($sale->meta, 'commerce.shipping.amount', 0)))
                        <tr>
                            <td>
                                <a href="{{ route('sales.commerce.show', $sale) }}" class="fw-semibold text-decoration-none">
                                    {{ $sale->sale_number }}
                                </a>
                                <div class="text-muted small">{{ ucfirst((string) $sale->source) }}</div>
                            </td>
                            <td>{{ $sale->customer_name_snapshot ?: optional($sale->contact)->name ?: '-' }}</td>
                            <td>
                                @include('shared.commerce.status-chip', ['type' => 'commerce', 'value' => data_get($sale->meta, 'commerce.status', $sale->status)])
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @include('shared.commerce.status-chip', ['type' => 'payment', 'value' => $sale->payment_status])
                                    @include('shared.commerce.status-chip', ['type' => 'payment_state', 'value' => data_get($sale->meta, 'commerce.payment.status', 'pending')])
                                </div>
                            </td>
                            <td>{{ number_format($shippingTotal, 0, ',', '.') }}</td>
                            <td>{{ number_format((float) $sale->grand_total, 0, ',', '.') }}</td>
                            <td>{{ optional($sale->transaction_date)->format('d M Y') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada order commerce.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($sales, 'links'))
            <div class="card-footer">{{ $sales->links() }}</div>
        @endif
    </div>
@endsection
