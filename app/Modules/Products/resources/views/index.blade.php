@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Products</h2>
        <div class="text-muted small">Master produk dengan harga, varian, barcode, SKU, dan konfigurasi stockable. Stok dikelola terpisah di module Inventory.</div>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary">Tambah Product</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('products.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Pencarian cepat</label>
                <input type="text" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama, SKU, barcode, variant">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tipe</label>
                <select name="type" class="form-select">
                    <option value="">Semua tipe</option>
                    <option value="simple" @selected(($filters['type'] ?? '') === 'simple')>Simple</option>
                    <option value="variant" @selected(($filters['type'] ?? '') === 'variant')>Variant</option>
                    <option value="service" @selected(($filters['type'] ?? '') === 'service')>Service</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">Semua category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Brand</label>
                <select name="brand_id" class="form-select">
                    <option value="">Semua brand</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) ($filters['brand_id'] ?? '') === (string) $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('products.bulk-action') }}">
    @csrf
    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row gap-2 justify-content-between">
            <div class="text-muted small">Bulk action hanya melakukan aktivasi, nonaktifkan, atau soft delete.</div>
            <div class="d-flex gap-2">
                <select name="action" class="form-select" style="min-width: 180px;">
                    <option value="">Pilih action</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="delete">Soft delete</option>
                </select>
                <button type="submit" class="btn btn-outline-primary">Apply</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th class="w-1"><input type="checkbox" class="form-check-input" data-check-all="products"></th>
                        <th>Product</th>
                        <th>Tipe</th>
                        <th>Category / Brand</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><input type="checkbox" class="form-check-input product-checkbox" name="product_ids[]" value="{{ $product->id }}"></td>
                            <td>
                                <a href="{{ route('products.show', $product) }}" class="text-decoration-none fw-semibold">{{ $product->name }}</a>
                                <div class="text-muted small">SKU: {{ $product->sku }} | Barcode: {{ $product->barcode ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-blue-lt text-blue">{{ ucfirst($product->type) }}</span>
                                @if($product->variant_count)
                                    <div class="text-muted small">{{ $product->variant_count }} varian</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $product->category?->name ?? '-' }}</div>
                                <div class="text-muted small">{{ $product->brand?->name ?? '-' }}</div>
                            </td>
                            <td>
                                <div>Jual: Rp {{ number_format((float) $product->sell_price, 0, ',', '.') }}</div>
                                <div class="text-muted small">Beli: Rp {{ number_format((float) $product->cost_price, 0, ',', '.') }}</div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}-lt text-{{ $product->is_active ? 'success' : 'secondary' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <form method="POST" action="{{ route('products.toggle-status', $product) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-icon btn-outline-warning" title="Toggle status">
                                            <i class="ti ti-switch-3"></i>
                                        </button>
                                    </form>
                                    <a class="btn btn-icon btn-outline-secondary" href="{{ route('products.edit', $product) }}" title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Soft delete product ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-icon btn-outline-danger" title="Delete">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada product.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $products->links() }}
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.querySelector('[data-check-all="products"]')?.addEventListener('change', function (event) {
    document.querySelectorAll('.product-checkbox').forEach((checkbox) => {
        checkbox.checked = event.target.checked;
    });
});
</script>
@endpush
