@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = $product->currency_code ?: 'IDR';
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $product->name }}</h2>
        <div class="text-muted small">SKU: {{ $product->sku }} | Barcode: {{ $product->barcode ?? '-' }}</div>
    </div>
    <div class="btn-list">
        <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-secondary">Edit</a>
        <a href="{{ route('products.index') }}" class="btn btn-primary">Kembali ke list</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Ringkasan Produk</h3></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">Tipe</div><div>{{ ucfirst($product->type) }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Status</div><div>{{ $product->is_active ? 'Active' : 'Inactive' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Category</div><div>{{ $product->category?->name ?? '-' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Brand</div><div>{{ $product->brand?->name ?? '-' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Unit</div><div>{{ $product->unit?->name ?? '-' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Stockable</div><div>{{ $product->track_stock ? 'Ya' : 'Tidak' }}</div></div>
                    <div class="col-12"><div class="text-muted small">Deskripsi</div><div>{{ $product->description ?: '-' }}</div></div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Harga</h3></div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><div class="text-muted small">Harga beli</div><div>{{ $money->format((float) $product->cost_price, $currency) }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Harga jual</div><div>{{ $money->format((float) $product->sell_price, $currency) }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Harga grosir default</div><div>{{ $product->wholesale_price !== null ? $money->format((float) $product->wholesale_price, $currency) : '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Harga member default</div><div>{{ $product->member_price !== null ? $money->format((float) $product->member_price, $currency) : '-' }}</div></div>
                </div>
                <div class="alert alert-secondary">
                    Harga di halaman ini adalah base/default pricing dari tabel `product_prices`. Jika ada promo aktif, source of truth tetap berasal dari module Discounts.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-vcenter">
                        <thead><tr><th>Level</th><th>Minimum Qty</th><th>Harga</th></tr></thead>
                        <tbody>
                            @forelse($product->prices as $price)
                                <tr>
                                    <td>{{ $price->priceLevel?->name ?? 'Custom' }}</td>
                                    <td>{{ number_format((float) $price->minimum_qty, 2, ',', '.') }}</td>
                                    <td>{{ $money->format((float) $price->price, $price->currency_code ?: $currency) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">Belum ada price level tambahan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Varian</h3></div>
            <div class="card-body">
                @if($product->variants->isEmpty())
                    <div class="text-muted">Produk ini tidak memiliki child variant.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead><tr><th>Variant</th><th>Atribut</th><th>SKU</th><th>Harga</th><th>Status stok</th></tr></thead>
                            <tbody>
                                @foreach($product->variants as $variant)
                                    <tr>
                                        <td>{{ $variant->name }}</td>
                                        <td>{{ $variant->optionValues->map(fn($item) => $item->group?->name . ': ' . $item->value)->implode(', ') ?: ($variant->attribute_summary ?: '-') }}</td>
                                        <td>{{ $variant->sku }}</td>
                                        <td>{{ $money->format((float) $variant->sell_price, $variant->currency_code ?: $currency) }}</td>
                                        <td><span class="badge bg-secondary-lt text-secondary">Lihat di Inventory</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Inventory Boundary</h3></div>
            <div class="card-body">
                <div class="text-muted mb-3">
                    Module Products hanya menyimpan master produk. Saldo stok, mutasi, adjustment, transfer, opening stock, stock card, dan low stock report berada di module Inventory.
                </div>
                @if(Route::has('inventory.stocks.index'))
                    <a href="{{ route('inventory.stocks.index', ['product_id' => $product->id]) }}" class="btn btn-primary w-100">Buka Inventory</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
