<x-guest-layout>
    @php($brand = $storefrontBrand ?? ['name' => config('app.name'), 'description' => null, 'logo_url' => null])
    <div class="container py-5">
        @include('storefront::public.partials.header', ['brand' => $brand, 'cartCount' => $cartCount ?? 0])
        @include('storefront::public.partials.flash')
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="rounded-3 overflow-hidden bg-light border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                                @if($brand['logo_url'])
                                    <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                @else
                                    <div class="fw-bold text-primary">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($brand['name'], 0, 2)) }}</div>
                                @endif
                            </div>
                            <div>
                                <div class="text-uppercase text-muted small fw-semibold mb-1">Status Pesanan</div>
                                <h1 class="h3 mb-1">{{ $sale->sale_number }}</h1>
                                <div class="text-muted">{{ $brand['name'] }}</div>
                            </div>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger mb-4">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @php
                            $commerceStatus = (string) data_get($sale->meta, 'commerce.status', 'pending_payment');
                            $paymentState = (string) data_get($sale->meta, 'commerce.payment.status', 'pending');
                            $statusLabel = match($commerceStatus) {
                                'paid' => 'Pembayaran diterima',
                                'ready_for_fulfillment' => 'Siap diproses',
                                'expired' => 'Pesanan kedaluwarsa',
                                'cancelled' => 'Pesanan dibatalkan',
                                default => 'Menunggu pembayaran',
                            };
                            $paymentLabel = match($paymentState) {
                                'paid' => 'Pembayaran sudah terkonfirmasi.',
                                'checkout_created' => 'Checkout online sudah dibuat dan bisa dibuka kembali.',
                                'expired' => 'Tagihan online sudah kedaluwarsa.',
                                'failed' => 'Pembayaran online belum berhasil.',
                                'cancelled' => 'Pembayaran dibatalkan.',
                                default => 'Tagihan masih menunggu pembayaran.',
                            };
                            $selectedRate = data_get($sale->meta, 'commerce.shipping.selected_rate', []);
                            $shippingTotal = (float) data_get($sale->totals_snapshot, 'shipping_total', data_get($sale->meta, 'commerce.shipping.amount', 0));
                        @endphp

                        <div class="alert alert-light border mb-4">
                            <div class="fw-semibold mb-1">{{ $statusLabel }}</div>
                            <div class="text-muted small">{{ $paymentLabel }}</div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="small text-muted">Nama</div>
                                <div class="fw-medium">{{ $sale->customer_name_snapshot ?: 'Guest' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">No. HP</div>
                                <div class="fw-medium">{{ $sale->customer_phone_snapshot ?: '-' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="small text-muted">Alamat</div>
                                <div class="fw-medium">{{ $sale->customer_address_snapshot ?: '-' }}</div>
                            </div>
                        </div>

                        @if($selectedRate)
                            <div class="alert alert-light border mb-4">
                                <div class="fw-semibold mb-1">Estimasi pengiriman</div>
                                <div>{{ ($selectedRate['courier_name'] ?? '-') }} &middot; {{ ($selectedRate['service_name'] ?? '-') }}</div>
                                <div class="text-muted small">Estimasi ongkir Rp{{ number_format($shippingTotal, 0, ',', '.') }} @if(!empty($selectedRate['etd'])) &middot; {{ $selectedRate['etd'] }} @endif</div>
                            </div>
                        @endif

                        @php($buyerAccess = data_get($sale->meta, 'commerce.buyer_access', []))
                        @if(($buyerAccess['status'] ?? null) === 'available')
                            <div class="alert alert-success mb-4">
                                <div class="fw-semibold mb-1">Akses buyer tersedia</div>
                                <div class="small">Instruksi pasca-pembayaran tersedia di bawah ini. Simpan halaman signed ini untuk membuka ulang akses order.</div>
                            </div>
                        @endif

                        <div class="table-responsive mb-4">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->items as $item)
                                        <tr>
                                            <td>{{ $item->product_name_snapshot }}</td>
                                            <td class="text-end">{{ number_format((float) $item->qty, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp{{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Subtotal Produk</th>
                                        <th class="text-end">Rp{{ number_format((float) $sale->subtotal, 0, ',', '.') }}</th>
                                    </tr>
                                    @if($shippingTotal > 0)
                                        <tr>
                                            <th colspan="3" class="text-end">Ongkir</th>
                                            <th class="text-end">Rp{{ number_format($shippingTotal, 0, ',', '.') }}</th>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th colspan="3" class="text-end">Grand Total</th>
                                        <th class="text-end">Rp{{ number_format((float) $sale->grand_total, 0, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @php
                            $deliveryPayloads = $sale->items->map(function ($item) {
                                return data_get($item->product_snapshot, 'payload.public_offer', []);
                            })->filter(fn ($payload) => is_array($payload) && $payload !== []);
                        @endphp
                        @if($deliveryPayloads->isNotEmpty())
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="fw-semibold mb-3">Delivery & Access</div>
                                    <div class="d-flex flex-column gap-3">
                                        @foreach($sale->items as $item)
                                            @php($offerPayload = data_get($item->product_snapshot, 'payload.public_offer', []))
                                            @continue(!is_array($offerPayload) || $offerPayload === [])
                                            <div class="border rounded-3 p-3">
                                                <div class="fw-semibold mb-1">{{ $item->product_name_snapshot }}</div>
                                                @if(!empty($offerPayload['delivery_instructions']))
                                                    <div class="text-muted small mb-2">{{ $offerPayload['delivery_instructions'] }}</div>
                                                @endif
                                                @if(!empty($offerPayload['download_url']))
                                                    <a href="{{ $offerPayload['download_url'] }}" target="_blank" rel="noreferrer" class="btn btn-sm btn-outline-primary">Buka Download</a>
                                                @endif
                                                @if(!empty($offerPayload['external_url']))
                                                    <a href="{{ $offerPayload['external_url'] }}" target="_blank" rel="noreferrer" class="btn btn-sm btn-outline-secondary">Buka Link</a>
                                                @endif
                                                @if(!empty($offerPayload['slot_note']))
                                                    <div class="small text-muted mt-2">{{ $offerPayload['slot_note'] }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        @php($timeline = collect(data_get($sale->meta, 'commerce.timeline', [])))
                        @if($timeline->isNotEmpty())
                            <div class="border rounded-3 p-3 mb-4">
                                <div class="fw-semibold mb-3">Perjalanan pesanan</div>
                                <div class="d-flex flex-column gap-3">
                                    @foreach($timeline as $entry)
                                        @php
                                            $event = (string) data_get($entry, 'event', 'updated');
                                            $label = match($event) {
                                                'pending_payment' => 'Menunggu pembayaran',
                                                'payment_checkout_created' => 'Checkout pembayaran dibuat',
                                                'paid' => 'Pembayaran diterima',
                                                'shipping_rate_selected' => 'Layanan pengiriman dipilih',
                                                'ready_for_fulfillment' => 'Pesanan siap diproses',
                                                'packing' => 'Pesanan sedang disiapkan',
                                                'shipped' => 'Pesanan sudah dikirim',
                                                'payment_cancelled' => 'Pembayaran dibatalkan',
                                                'payment_failed' => 'Pembayaran belum berhasil',
                                                'expired' => 'Pesanan kedaluwarsa',
                                                'cancelled' => 'Pesanan dibatalkan',
                                                default => 'Status pesanan diperbarui',
                                            };
                                        @endphp
                                        <div class="d-flex justify-content-between gap-3">
                                            <div>
                                                <div class="fw-medium">{{ $label }}</div>
                                                @if(data_get($entry, 'context.tracking_number'))
                                                    <div class="text-muted small">Resi: {{ data_get($entry, 'context.tracking_number') }}</div>
                                                @endif
                                            </div>
                                            <div class="text-muted small text-nowrap">{{ \Illuminate\Support\Carbon::parse((string) data_get($entry, 'at'))->translatedFormat('d M Y H:i') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @php($requestedMethod = data_get($sale->meta, 'commerce.payment.requested_method', 'manual'))
                        @php($canRetryPayment = $requestedMethod !== 'manual' && in_array($commerceStatus, ['pending_payment', 'expired'], true) && (float) $sale->balance_due > 0)

                        <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
                            <div class="text-muted small">Simpan tautan ini jika Anda perlu membuka ulang status pesanan: {{ $publicOrderUrl }}</div>
                            <div class="d-flex gap-2">
                                @if($canRetryPayment)
                                    <form method="POST" action="{{ $publicRetryPaymentUrl }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">Bayar Sekarang</button>
                                    </form>
                                @endif
                                <a href="{{ route('storefront.public.index') }}" class="btn btn-outline-secondary">Kembali ke katalog</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
