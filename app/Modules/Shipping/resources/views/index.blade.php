@extends('layouts.admin')

@section('title', 'Shipping')

@section('content')
    @php
        $quoteInput = old() ?: ($quoteInput ?? []);
        $quoteResult = $quoteResult ?? null;
    @endphp
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Commerce</div>
                <h2 class="page-title">Shipping</h2>
                <p class="text-muted mb-0">Kelola rate, antrian kirim, dan resi order delivery dari satu layar operasional.</p>
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
                    <div class="text-muted small mb-1">Delivery Orders</div>
                    <div class="h2 mb-1">{{ number_format((int) $shippingMetrics['delivery_orders']) }}</div>
                    <div class="text-muted small">Total order delivery aktif</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Menunggu Rate</div>
                    <div class="h2 mb-1">{{ number_format((int) $shippingMetrics['waiting_rate']) }}</div>
                    <div class="text-muted small">Order belum punya ongkir terpilih</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Siap Dikirim</div>
                    <div class="h2 mb-1">{{ number_format((int) $shippingMetrics['ready_ship']) }}</div>
                    <div class="text-muted small">Sudah siap dibuatkan resi</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Perlu Perhatian</div>
                    <div class="h2 mb-1">{{ number_format((int) $shippingMetrics['attention']) }}</div>
                    <div class="text-muted small">{{ number_format((int) $shippingMetrics['shipped_today']) }} order terkirim hari ini</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Queue Pengiriman</h3>
                        <div class="text-muted small mt-1">Kelola order delivery, ongkir terpilih, dan pembuatan resi dari satu layar operasional.</div>
                    </div>
                    <div>
                        @if($activeShippingProviderLabel)
                            <span class="badge bg-azure-lt text-azure">{{ $activeShippingProviderLabel }}</span>
                        @else
                            <span class="badge bg-secondary-lt text-secondary">Manual</span>
                        @endif
                    </div>
                </div>
                <div class="card-body border-bottom">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('shipping.index') }}" class="btn btn-sm {{ ($currentFilter ?? 'active') === 'active' ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
                        <a href="{{ route('shipping.index', ['filter' => 'waiting_rate']) }}" class="btn btn-sm {{ ($currentFilter ?? '') === 'waiting_rate' ? 'btn-primary' : 'btn-outline-secondary' }}">Menunggu Rate</a>
                        <a href="{{ route('shipping.index', ['filter' => 'ready_to_ship']) }}" class="btn btn-sm {{ ($currentFilter ?? '') === 'ready_to_ship' ? 'btn-primary' : 'btn-outline-secondary' }}">Siap Dikirim</a>
                        <a href="{{ route('shipping.index', ['filter' => 'shipped']) }}" class="btn btn-sm {{ ($currentFilter ?? '') === 'shipped' ? 'btn-primary' : 'btn-outline-secondary' }}">Sudah Dikirim</a>
                        <a href="{{ route('shipping.index', ['filter' => 'attention']) }}" class="btn btn-sm {{ ($currentFilter ?? '') === 'attention' ? 'btn-primary' : 'btn-outline-secondary' }}">Perlu Perhatian</a>
                    </div>
                </div>
                <div class="card-body border-bottom bg-body-tertiary">
                    <form id="shipping-bulk-rate-form" method="POST" action="{{ route('shipping.bulk') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label">Kurir</label>
                            <input type="text" name="courier_name" class="form-control form-control-sm" placeholder="JNE">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Layanan</label>
                            <input type="text" name="service_name" class="form-control form-control-sm" placeholder="REG">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ongkir</label>
                            <input type="number" name="price" class="form-control form-control-sm" placeholder="18000" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ETD</label>
                            <input type="text" name="etd" class="form-control form-control-sm" placeholder="2-3 hari">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Terapkan ke Terpilih</button>
                        </div>
                    </form>
                    <div class="text-muted small mt-2">Pilih beberapa order delivery pada tabel lalu simpan rate manual yang sama untuk semuanya.</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th class="w-1">
                                    <input class="form-check-input" type="checkbox" data-bulk-toggle="shipping-bulk-rate-form">
                                </th>
                                <th>Order</th>
                                <th>Penerima</th>
                                <th>Rate</th>
                                <th>Shipping</th>
                                <th>Total</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($orders ?? collect()) as $order)
                                @php($selectedRate = data_get($order->meta, 'commerce.shipping.selected_rate', []))
                                @php($shippingTotal = (float) data_get($order->totals_snapshot, 'shipping_total', data_get($order->meta, 'commerce.shipping.amount', 0)))
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="sale_ids[]" value="{{ $order->id }}" form="shipping-bulk-rate-form">
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><a href="{{ route('sales.commerce.show', $order) }}" class="text-decoration-none">{{ $order->sale_number }}</a></div>
                                        <div class="text-muted small">{{ optional($order->transaction_date)->format('d M Y H:i') }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $order->customer_name_snapshot ?: '-' }}</div>
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit((string) $order->customer_address_snapshot, 72) }}</div>
                                    </td>
                                    <td>
                                        @if($selectedRate)
                                            <div class="fw-semibold">{{ $selectedRate['courier_name'] ?? '-' }} &middot; {{ $selectedRate['service_name'] ?? '-' }}</div>
                                            <div class="text-muted small">Rp{{ number_format((float) ($selectedRate['price'] ?? 0), 0, ',', '.') }} &middot; {{ $selectedRate['etd'] ?? '-' }}</div>
                                        @else
                                            <span class="text-muted small">Belum ada rate</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>@include('shared.commerce.status-chip', ['type' => 'shipping', 'value' => data_get($order->meta, 'commerce.shipping.status', 'pending')])</div>
                                        @if(data_get($order->meta, 'commerce.shipping.tracking_number'))
                                            <div class="text-muted small mt-1">Resi: {{ data_get($order->meta, 'commerce.shipping.tracking_number') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-medium">Rp{{ number_format((float) $order->grand_total, 0, ',', '.') }}</div>
                                        @if($shippingTotal > 0)
                                            <div class="text-muted small">Ongkir Rp{{ number_format($shippingTotal, 0, ',', '.') }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column gap-2 align-items-end">
                                            @if(!$selectedRate)
                                                <form method="POST" action="{{ route('shipping.rate', $order) }}" class="d-flex gap-2 flex-wrap justify-content-end">
                                                    @csrf
                                                    <input type="text" name="courier_name" class="form-control form-control-sm" placeholder="Kurir" style="width: 96px;">
                                                    <input type="text" name="service_name" class="form-control form-control-sm" placeholder="Layanan" style="width: 96px;">
                                                    <input type="number" name="price" class="form-control form-control-sm" placeholder="Harga" style="width: 108px;">
                                                    <input type="text" name="etd" class="form-control form-control-sm" placeholder="ETD" style="width: 72px;">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Simpan Rate</button>
                                                </form>
                                            @endif
                                            @if((string) data_get($order->meta, 'commerce.shipping.status', 'pending') !== 'shipped')
                                                <form method="POST" action="{{ route('shipping.ship', $order) }}" class="d-flex gap-2 flex-wrap justify-content-end">
                                                    @csrf
                                                    <input type="text" name="tracking_number" class="form-control form-control-sm" placeholder="No. Resi" style="width: 132px;">
                                                    <input type="text" name="courier_name" class="form-control form-control-sm" placeholder="Kurir" style="width: 96px;" value="{{ data_get($selectedRate, 'courier_name') }}">
                                                    <input type="text" name="service_name" class="form-control form-control-sm" placeholder="Layanan" style="width: 96px;" value="{{ data_get($selectedRate, 'service_name') }}">
                                                    <button type="submit" class="btn btn-sm btn-primary">Buat Resi Manual</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada order delivery pada filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h3 class="card-title mb-0">Provider Aktif</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        @if($activeShippingProviderLabel)
                            <div class="fw-semibold">{{ $activeShippingProviderLabel }}</div>
                            <div class="text-muted small">{{ $activeShippingProviderConfigured ? 'Siap dipakai untuk quote ongkir dan workflow delivery.' : 'Provider belum lengkap dikonfigurasi.' }}</div>
                        @else
                            <div class="fw-semibold">Manual / Tanpa API</div>
                            <div class="text-muted small">Order tetap bisa diproses, tetapi rate dan resi diinput manual.</div>
                        @endif
                    </div>
                    <a href="{{ route('settings.index', ['tab' => 'shipping-provider']) }}" class="btn btn-outline-primary w-100">Buka Pengaturan Provider</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Perlu Perhatian</h3>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Belum pilih rate</span>
                        <span class="badge bg-yellow-lt text-yellow">{{ number_format((int) ($attentionReasons['missing_rate'] ?? 0)) }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Status shipping masih pending</span>
                        <span class="badge bg-yellow-lt text-yellow">{{ number_format((int) ($attentionReasons['pending_status'] ?? 0)) }}</span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Utility Quote Tester</h3>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('shipping.quote') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Origin Postal Code</label>
                            <input type="text" name="origin_postal_code" class="form-control" value="{{ $quoteInput['origin_postal_code'] ?? '' }}" placeholder="12440">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Destination Postal Code</label>
                            <input type="text" name="destination_postal_code" class="form-control" value="{{ $quoteInput['destination_postal_code'] ?? '' }}" placeholder="12240">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Origin Area ID</label>
                            <input type="text" name="origin_area_id" class="form-control" value="{{ $quoteInput['origin_area_id'] ?? '' }}" placeholder="Untuk RajaOngkir">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Destination Area ID</label>
                            <input type="text" name="destination_area_id" class="form-control" value="{{ $quoteInput['destination_area_id'] ?? '' }}" placeholder="Untuk RajaOngkir">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Couriers</label>
                            <input type="text" name="couriers" class="form-control" value="{{ $quoteInput['couriers'] ?? '' }}" placeholder="jne,sicepat atau jne:sicepat">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nama Item</label>
                            <input type="text" name="item_name" class="form-control" value="{{ $quoteInput['item_name'] ?? 'Produk Demo' }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nilai Item</label>
                            <input type="number" name="item_value" class="form-control" value="{{ $quoteInput['item_value'] ?? 100000 }}" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Berat (gram)</label>
                            <input type="number" name="item_weight" class="form-control" value="{{ $quoteInput['item_weight'] ?? 1000 }}" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Qty</label>
                            <input type="number" name="item_quantity" class="form-control" value="{{ $quoteInput['item_quantity'] ?? 1 }}" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Panjang</label>
                            <input type="number" name="item_length" class="form-control" value="{{ $quoteInput['item_length'] ?? '' }}" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lebar</label>
                            <input type="number" name="item_width" class="form-control" value="{{ $quoteInput['item_width'] ?? '' }}" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tinggi</label>
                            <input type="number" name="item_height" class="form-control" value="{{ $quoteInput['item_height'] ?? '' }}" min="1">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">Cek Quote</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($quoteResult)
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Hasil Quote {{ strtoupper((string) ($quoteResult['provider'] ?? '')) }}</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Courier</th>
                            <th>Service</th>
                            <th>ETD</th>
                            <th class="text-end">Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($quoteResult['options'] ?? []) as $option)
                            <tr>
                                <td>{{ $option['courier_name'] ?: $option['courier_code'] }}</td>
                                <td>{{ $option['service_name'] ?: $option['service_code'] }}</td>
                                <td>{{ $option['etd'] ?: '-' }}</td>
                                <td class="text-end">Rp{{ number_format((float) ($option['price'] ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Tidak ada rate yang dikembalikan provider.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

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
