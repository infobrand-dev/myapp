@extends('layouts.admin')

@section('title', 'Fulfillment')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Commerce</div>
                <h2 class="page-title">Fulfillment</h2>
                <p class="text-muted mb-0">Pisahkan order yang perlu disiapkan, packing, dan handoff ke shipping atau pickup.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('storefront.index') }}" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Queue</div>
                    <div class="h2 mb-1">{{ number_format((int) $fulfillmentMetrics['orders']) }}</div>
                    <div class="text-muted small">Semua order commerce yang sudah dibayar</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Packing</div>
                    <div class="h2 mb-1">{{ number_format((int) $fulfillmentMetrics['packing']) }}</div>
                    <div class="text-muted small">Sedang dipersiapkan tim</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Ready</div>
                    <div class="h2 mb-1">{{ number_format((int) $fulfillmentMetrics['ready']) }}</div>
                    <div class="text-muted small">Siap handoff ke pickup / shipping</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Mode Order</div>
                    <div class="h4 mb-1">{{ number_format((int) $fulfillmentMetrics['pickup']) }} pickup</div>
                    <div class="text-muted small">{{ number_format((int) $fulfillmentMetrics['delivery']) }} delivery</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h3 class="card-title mb-0">Queue Fulfillment</h3>
                </div>
                <div class="card-body border-bottom">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('fulfillment.index') }}" class="btn btn-sm {{ ($currentFilter ?? 'active') === 'active' ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
                        <a href="{{ route('fulfillment.index', ['filter' => 'packing']) }}" class="btn btn-sm {{ ($currentFilter ?? '') === 'packing' ? 'btn-primary' : 'btn-outline-secondary' }}">Packing</a>
                        <a href="{{ route('fulfillment.index', ['filter' => 'ready']) }}" class="btn btn-sm {{ ($currentFilter ?? '') === 'ready' ? 'btn-primary' : 'btn-outline-secondary' }}">Ready</a>
                        <a href="{{ route('fulfillment.index', ['filter' => 'pickup']) }}" class="btn btn-sm {{ ($currentFilter ?? '') === 'pickup' ? 'btn-primary' : 'btn-outline-secondary' }}">Pickup</a>
                        <a href="{{ route('fulfillment.index', ['filter' => 'delivery']) }}" class="btn btn-sm {{ ($currentFilter ?? '') === 'delivery' ? 'btn-primary' : 'btn-outline-secondary' }}">Delivery</a>
                    </div>
                </div>
                <div class="card-body border-bottom bg-body-tertiary">
                    <form id="fulfillment-bulk-form" method="POST" action="{{ route('fulfillment.bulk') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-8">
                            <label class="form-label">Catatan Operasional</label>
                            <input type="text" name="note" class="form-control form-control-sm" placeholder="Catatan untuk order terpilih">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="action" value="packing" class="btn btn-outline-primary btn-sm w-100">Bulk Packing</button>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="action" value="ready" class="btn btn-primary btn-sm w-100">Bulk Ready</button>
                        </div>
                    </form>
                    <div class="text-muted small mt-2">Gunakan checklist pada tabel untuk memindahkan beberapa order sekaligus ke packing atau ready handoff.</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th class="w-1">
                                    <input class="form-check-input" type="checkbox" data-bulk-toggle="fulfillment-bulk-form">
                                </th>
                                <th>Order</th>
                                <th>Mode</th>
                                <th>Status</th>
                                <th>Customer</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($orders ?? collect()) as $order)
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="sale_ids[]" value="{{ $order->id }}" form="fulfillment-bulk-form">
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->sale_number }}</div>
                                        <div class="text-muted small">Rp{{ number_format((float) $order->grand_total, 0, ',', '.') }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ strtoupper((string) data_get($order->meta, 'commerce.fulfillment_method', 'pickup')) }}</div>
                                        @if(data_get($order->meta, 'commerce.shipping.selected_rate.courier_name'))
                                            <div class="text-muted small">{{ data_get($order->meta, 'commerce.shipping.selected_rate.courier_name') }} &middot; {{ data_get($order->meta, 'commerce.shipping.selected_rate.service_name') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $fulfillmentStatus = (string) data_get($order->meta, 'commerce.fulfillment.status', 'pending');
                                            $paymentState = (string) data_get($order->meta, 'commerce.payment.status', 'pending');
                                        @endphp
                                        @include('shared.commerce.status-chip', ['type' => 'fulfillment', 'value' => $fulfillmentStatus])
                                        <div class="text-muted small mt-1">
                                            {{ match($paymentState) {
                                                'paid' => 'Pembayaran terkonfirmasi',
                                                'checkout_created' => 'Checkout online dibuat',
                                                'expired' => 'Tagihan expired',
                                                'failed' => 'Pembayaran gagal',
                                                'cancelled' => 'Pembayaran dibatalkan',
                                                default => 'Menunggu pembayaran',
                                            } }}
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $order->customer_name_snapshot ?: '-' }}</div>
                                        <div class="text-muted small">{{ $order->customer_phone_snapshot ?: '-' }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                                            @if((string) data_get($order->meta, 'commerce.fulfillment.status', 'pending') !== 'packing')
                                                <form method="POST" action="{{ route('fulfillment.packing', $order) }}" class="d-flex gap-2 align-items-center">
                                                    @csrf
                                                    <input type="text" name="note" class="form-control form-control-sm" placeholder="Catatan packing" style="width: 140px;">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Mulai Packing</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('fulfillment.ready', $order) }}" class="d-flex gap-2 align-items-center">
                                                @csrf
                                                <input type="text" name="note" class="form-control form-control-sm" placeholder="Catatan akhir" style="width: 132px;">
                                                <button type="submit" class="btn btn-sm btn-primary">Tandai Ready</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada order yang menunggu fulfillment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header"><h3 class="card-title mb-0">Ringkasan Queue</h3></div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Order Packing</span>
                        <span class="badge bg-orange-lt text-orange">{{ number_format((int) $fulfillmentMetrics['packing']) }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Order Ready</span>
                        <span class="badge bg-green-lt text-green">{{ number_format((int) $fulfillmentMetrics['ready']) }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Pickup</span>
                        <span class="badge bg-secondary-lt text-secondary">{{ number_format((int) $fulfillmentMetrics['pickup']) }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Delivery</span>
                        <span class="badge bg-azure-lt text-azure">{{ number_format((int) $fulfillmentMetrics['delivery']) }}</span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Handoff Cepat</h3></div>
                <div class="list-group list-group-flush">
                    @forelse($readyQueue as $order)
                        <a href="{{ data_get($order->meta, 'commerce.fulfillment_method') === 'delivery' ? route('shipping.index') : route('sales.commerce.show', $order) }}" class="list-group-item list-group-item-action">
                            <div class="fw-semibold">{{ $order->sale_number }}</div>
                            <div class="text-muted small">{{ data_get($order->meta, 'commerce.fulfillment_method') === 'delivery' ? 'Lanjut ke shipping queue' : 'Siap untuk pickup/customer handoff' }}</div>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">Belum ada order yang siap handoff.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-bulk-toggle]').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                const formId = toggle.getAttribute('data-bulk-toggle');
                document.querySelectorAll('input[form="' + formId + '"][name="sale_ids[]"]').forEach(function (checkbox) {
                    checkbox.checked = toggle.checked;
                });
            });
        });
    </script>
@endsection
