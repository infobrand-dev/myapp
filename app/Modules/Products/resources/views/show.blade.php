@extends('layouts.admin')

@section('content')
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
                    <div class="col-md-6"><div class="text-muted small">Track stok</div><div>{{ $product->track_stock ? 'Ya' : 'Tidak' }}</div></div>
                    <div class="col-12"><div class="text-muted small">Deskripsi</div><div>{{ $product->description ?: '-' }}</div></div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Harga</h3></div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><div class="text-muted small">Harga beli</div><div>Rp {{ number_format((float) $product->cost_price, 0, ',', '.') }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Harga jual</div><div>Rp {{ number_format((float) $product->sell_price, 0, ',', '.') }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Harga grosir</div><div>{{ $product->wholesale_price !== null ? 'Rp ' . number_format((float) $product->wholesale_price, 0, ',', '.') : '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Harga member</div><div>{{ $product->member_price !== null ? 'Rp ' . number_format((float) $product->member_price, 0, ',', '.') : '-' }}</div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-vcenter">
                        <thead><tr><th>Level</th><th>Minimum Qty</th><th>Harga</th></tr></thead>
                        <tbody>
                            @forelse($product->prices as $price)
                                <tr>
                                    <td>{{ $price->priceLevel?->name ?? 'Custom' }}</td>
                                    <td>{{ number_format((float) $price->minimum_qty, 2, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $price->price, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">Belum ada multi price level tambahan.</td></tr>
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
                            <thead><tr><th>Variant</th><th>Atribut</th><th>SKU</th><th>Harga</th><th>Stok</th></tr></thead>
                            <tbody>
                                @foreach($product->variants as $variant)
                                    <tr>
                                        <td>{{ $variant->name }}</td>
                                        <td>{{ $variant->optionValues->map(fn($item) => $item->group?->name . ': ' . $item->value)->implode(', ') ?: ($variant->attribute_summary ?: '-') }}</td>
                                        <td>{{ $variant->sku }}</td>
                                        <td>Rp {{ number_format((float) $variant->sell_price, 0, ',', '.') }}</td>
                                        <td>{{ number_format((float) ($variant->total_stock ?? 0), 2, ',', '.') }}</td>
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
            <div class="card-header"><h3 class="card-title">Stok per Lokasi</h3></div>
            <div class="card-body">
                @if(!$product->track_stock)
                    <div class="text-muted">Produk ini ditandai sebagai non-stock / service.</div>
                @else
                    <div class="mb-3"><div class="text-muted small">Min stok</div><div>{{ number_format((float) $product->min_stock, 2, ',', '.') }}</div></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-vcenter">
                            <thead><tr><th>Lokasi</th><th>Qty</th><th>Reserved</th></tr></thead>
                            <tbody>
                                @forelse($product->stocks as $stock)
                                    <tr>
                                        <td>{{ $stock->location?->name ?? '-' }}</td>
                                        <td>{{ number_format((float) $stock->quantity, 2, ',', '.') }}</td>
                                        <td>{{ number_format((float) $stock->reserved_quantity, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">Belum ada data stok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
