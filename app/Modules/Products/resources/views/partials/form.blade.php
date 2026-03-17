@php
    $isEdit = $product->exists;
    $variantRows = old('variants', $product->variants->map(function ($variant) {
        return [
            'id' => $variant->id,
            'name' => $variant->name,
            'attribute_summary' => $variant->attribute_summary,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'cost_price' => $variant->cost_price,
            'sell_price' => $variant->sell_price,
            'wholesale_price' => $variant->wholesale_price,
            'member_price' => $variant->member_price,
            'is_active' => $variant->is_active,
            'track_stock' => $variant->track_stock,
        ];
    })->values()->all());
    $priceRows = old('price_levels', $product->prices->map(function ($price) {
        return [
            'price_level_id' => $price->product_price_level_id,
            'price' => $price->price,
            'minimum_qty' => $price->minimum_qty,
        ];
    })->values()->all());
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $submitRoute }}" enctype="multipart/form-data">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Data Dasar Produk</h3></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipe produk</label>
                        <select name="type" class="form-select" id="product-type">
                            <option value="simple" @selected(old('type', $product->type) === 'simple')>Simple product</option>
                            <option value="variant" @selected(old('type', $product->type) === 'variant')>Variant product</option>
                            <option value="service" @selected(old('type', $product->type) === 'service')>Non-stock / service</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Nama produk</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $product->slug) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Pilih category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="new_category_name" class="form-control mt-2" placeholder="Atau buat category baru" value="{{ old('new_category_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" class="form-select">
                            <option value="">Pilih brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="new_brand_name" class="form-control mt-2" placeholder="Atau buat brand baru" value="{{ old('new_brand_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit</label>
                        <select name="unit_id" class="form-select">
                            <option value="">Pilih unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('unit_id', $product->unit_id) === (string) $unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>
                            @endforeach
                        </select>
                        <div class="row g-2 mt-0">
                            <div class="col-7"><input type="text" name="new_unit_name" class="form-control mt-2" placeholder="Unit baru" value="{{ old('new_unit_name') }}"></div>
                            <div class="col-5"><input type="text" name="new_unit_code" class="form-control mt-2" placeholder="Kode" value="{{ old('new_unit_code') }}"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Harga Produk</h3></div>
                <div class="card-body row g-3">
                    <div class="col-md-3"><label class="form-label">Harga beli</label><input type="number" step="0.01" min="0" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price ?? 0) }}" required></div>
                    <div class="col-md-3"><label class="form-label">Harga jual</label><input type="number" step="0.01" min="0" name="sell_price" class="form-control" value="{{ old('sell_price', $product->sell_price ?? 0) }}" required></div>
                    <div class="col-md-3"><label class="form-label">Harga grosir</label><input type="number" step="0.01" min="0" name="wholesale_price" class="form-control" value="{{ old('wholesale_price', $product->wholesale_price) }}"></div>
                    <div class="col-md-3"><label class="form-label">Harga member</label><input type="number" step="0.01" min="0" name="member_price" class="form-control" value="{{ old('member_price', $product->member_price) }}"></div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Multi Price Level</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-price-row">Tambah level harga</button>
                </div>
                <div class="card-body" id="price-rows">
                    @foreach($priceRows ?: [['price_level_id' => '', 'price' => '', 'minimum_qty' => 1]] as $index => $priceRow)
                        <div class="row g-2 align-items-end mb-2 price-row">
                            <div class="col-md-5">
                                <label class="form-label">Price level</label>
                                <select name="price_levels[{{ $index }}][price_level_id]" class="form-select">
                                    <option value="">Pilih level</option>
                                    @foreach($priceLevels as $level)
                                        <option value="{{ $level->id }}" @selected((string) ($priceRow['price_level_id'] ?? '') === (string) $level->id)>{{ $level->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label">Harga</label><input type="number" min="0" step="0.01" name="price_levels[{{ $index }}][price]" class="form-control" value="{{ $priceRow['price'] ?? '' }}"></div>
                            <div class="col-md-3"><label class="form-label">Min qty</label><input type="number" min="1" step="0.01" name="price_levels[{{ $index }}][minimum_qty]" class="form-control" value="{{ $priceRow['minimum_qty'] ?? 1 }}"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-price-row"><i class="ti ti-x"></i></button></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card mt-3" id="variant-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">Varian Produk</h3>
                        <div class="text-muted small">Format atribut: `Ukuran:M|Warna:Merah`.</div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-variant-row">Tambah variant</button>
                </div>
                <div class="card-body" id="variant-rows">
                    @foreach($variantRows ?: [[
                        'id' => '',
                        'name' => '',
                        'attribute_summary' => '',
                        'sku' => '',
                        'barcode' => '',
                        'cost_price' => 0,
                        'sell_price' => 0,
                        'wholesale_price' => '',
                        'member_price' => '',
                        'is_active' => true,
                        'track_stock' => true,
                    ]] as $index => $variant)
                        <div class="border rounded p-3 mb-3 variant-row">
                            <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant['id'] ?? '' }}">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Nama variant</label><input type="text" name="variants[{{ $index }}][name]" class="form-control" value="{{ $variant['name'] ?? '' }}"></div>
                                <div class="col-md-4"><label class="form-label">Atribut</label><input type="text" name="variants[{{ $index }}][attribute_summary]" class="form-control" value="{{ $variant['attribute_summary'] ?? '' }}" placeholder="Ukuran:M|Warna:Merah"></div>
                                <div class="col-md-4"><label class="form-label">SKU variant</label><input type="text" name="variants[{{ $index }}][sku]" class="form-control" value="{{ $variant['sku'] ?? '' }}"></div>
                                <div class="col-md-3"><label class="form-label">Barcode</label><input type="text" name="variants[{{ $index }}][barcode]" class="form-control" value="{{ $variant['barcode'] ?? '' }}"></div>
                                <div class="col-md-3"><label class="form-label">Harga beli</label><input type="number" min="0" step="0.01" name="variants[{{ $index }}][cost_price]" class="form-control" value="{{ $variant['cost_price'] ?? 0 }}"></div>
                                <div class="col-md-3"><label class="form-label">Harga jual</label><input type="number" min="0" step="0.01" name="variants[{{ $index }}][sell_price]" class="form-control" value="{{ $variant['sell_price'] ?? 0 }}"></div>
                                <div class="col-md-3"><label class="form-label">Harga grosir</label><input type="number" min="0" step="0.01" name="variants[{{ $index }}][wholesale_price]" class="form-control" value="{{ $variant['wholesale_price'] ?? '' }}"></div>
                                <div class="col-md-3"><label class="form-label">Harga member</label><input type="number" min="0" step="0.01" name="variants[{{ $index }}][member_price]" class="form-control" value="{{ $variant['member_price'] ?? '' }}"></div>
                                <div class="col-md-3"><div class="form-check mt-4"><input type="hidden" name="variants[{{ $index }}][is_active]" value="0"><input class="form-check-input" type="checkbox" name="variants[{{ $index }}][is_active]" value="1" @checked((bool) ($variant['is_active'] ?? true))><label class="form-check-label">Active</label></div></div>
                                <div class="col-md-2"><div class="form-check mt-4"><input type="hidden" name="variants[{{ $index }}][track_stock]" value="0"><input class="form-check-input" type="checkbox" name="variants[{{ $index }}][track_stock]" value="1" @checked((bool) ($variant['track_stock'] ?? true))><label class="form-check-label">Track stock</label></div></div>
                                <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100 remove-variant-row"><i class="ti ti-trash"></i></button></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Status dan Media</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12"><div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $product->is_active))><label class="form-check-label">Status active</label></div></div>
                    <div class="col-12" id="track-stock-wrapper"><div class="form-check"><input type="hidden" name="track_stock" value="0"><input class="form-check-input" type="checkbox" name="track_stock" value="1" @checked((bool) old('track_stock', $product->track_stock))><label class="form-check-label">Track stock</label></div></div>
                    <div class="col-12">
                        <div class="alert alert-secondary mb-0">
                            Stok, minimum stock, opening stock, adjustment, transfer, dan histori mutasi dikelola di module Inventory.
                        </div>
                    </div>
                    <div class="col-12"><label class="form-label">Featured image</label><input type="file" name="featured_image" class="form-control" accept="image/*"></div>
                    <div class="col-12"><label class="form-label">Gallery images</label><input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple></div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Update Product' : 'Simpan Product' }}</button>
                <a href="{{ $isEdit ? route('products.show', $product) : route('products.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
(() => {
    const typeSelect = document.getElementById('product-type');
    const variantCard = document.getElementById('variant-card');
    const trackStockWrapper = document.getElementById('track-stock-wrapper');
    const variantRows = document.getElementById('variant-rows');
    const priceRows = document.getElementById('price-rows');

    const refreshVisibility = () => {
        const type = typeSelect?.value || 'simple';
        if (variantCard) variantCard.style.display = type === 'variant' ? '' : 'none';
        if (trackStockWrapper) trackStockWrapper.style.display = type === 'service' ? 'none' : '';
    };

    const reindexRows = (container, rowSelector) => {
        container.querySelectorAll(rowSelector).forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/\[\d+\]/, `[${index}]`);
            });
        });
    };

    document.getElementById('add-price-row')?.addEventListener('click', () => {
        const index = priceRows.querySelectorAll('.price-row').length;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-end mb-2 price-row';
        row.innerHTML = `
            <div class="col-md-5"><label class="form-label">Price level</label><select name="price_levels[${index}][price_level_id]" class="form-select"><option value="">Pilih level</option>@foreach($priceLevels as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Harga</label><input type="number" min="0" step="0.01" name="price_levels[${index}][price]" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Min qty</label><input type="number" min="1" step="0.01" name="price_levels[${index}][minimum_qty]" class="form-control" value="1"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-price-row"><i class="ti ti-x"></i></button></div>
        `;
        priceRows.appendChild(row);
    });

    priceRows?.addEventListener('click', (event) => {
        if (!event.target.closest('.remove-price-row')) return;
        event.target.closest('.price-row')?.remove();
        reindexRows(priceRows, '.price-row');
    });

    document.getElementById('add-variant-row')?.addEventListener('click', () => {
        const index = variantRows.querySelectorAll('.variant-row').length;
        const row = document.createElement('div');
        row.className = 'border rounded p-3 mb-3 variant-row';
        row.innerHTML = `
            <input type="hidden" name="variants[${index}][id]" value="">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Nama variant</label><input type="text" name="variants[${index}][name]" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Atribut</label><input type="text" name="variants[${index}][attribute_summary]" class="form-control" placeholder="Ukuran:M|Warna:Merah"></div>
                <div class="col-md-4"><label class="form-label">SKU variant</label><input type="text" name="variants[${index}][sku]" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Barcode</label><input type="text" name="variants[${index}][barcode]" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Harga beli</label><input type="number" min="0" step="0.01" name="variants[${index}][cost_price]" class="form-control" value="0"></div>
                <div class="col-md-3"><label class="form-label">Harga jual</label><input type="number" min="0" step="0.01" name="variants[${index}][sell_price]" class="form-control" value="0"></div>
                <div class="col-md-3"><label class="form-label">Harga grosir</label><input type="number" min="0" step="0.01" name="variants[${index}][wholesale_price]" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Harga member</label><input type="number" min="0" step="0.01" name="variants[${index}][member_price]" class="form-control"></div>
                <div class="col-md-3"><div class="form-check mt-4"><input type="hidden" name="variants[${index}][is_active]" value="0"><input class="form-check-input" type="checkbox" name="variants[${index}][is_active]" value="1" checked><label class="form-check-label">Active</label></div></div>
                <div class="col-md-2"><div class="form-check mt-4"><input type="hidden" name="variants[${index}][track_stock]" value="0"><input class="form-check-input" type="checkbox" name="variants[${index}][track_stock]" value="1" checked><label class="form-check-label">Track stock</label></div></div>
                <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100 remove-variant-row"><i class="ti ti-trash"></i></button></div>
            </div>
        `;
        variantRows.appendChild(row);
    });

    variantRows?.addEventListener('click', (event) => {
        if (!event.target.closest('.remove-variant-row')) return;
        event.target.closest('.variant-row')?.remove();
        reindexRows(variantRows, '.variant-row');
    });

    typeSelect?.addEventListener('change', refreshVisibility);
    refreshVisibility();
})();
</script>
@endpush
