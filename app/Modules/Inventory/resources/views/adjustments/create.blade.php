@extends('layouts.admin')

@section('title', 'Buat Stock Adjustment')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori · Stock Adjustment</div>
            <h2 class="page-title">Buat Stock Adjustment</h2>
            <p class="text-muted mb-0">Stok berubah setelah dokumen difinalisasi.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('inventory.adjustments.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Adjustment Header</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        @include('shared.accounting.field-label', [
                            'label' => 'Location',
                            'required' => true,
                            'tooltip' => 'Lokasi stok yang akan disesuaikan. Pastikan lokasi sesuai dengan tempat stok fisik diperiksa.',
                        ])
                        <select name="inventory_location_id" class="form-select" required>
                            <option value="">Pilih lokasi</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('inventory_location_id') === (string) $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        @include('shared.accounting.field-label', [
                            'label' => 'Adjustment Date',
                            'required' => true,
                            'tooltip' => 'Tanggal saat koreksi stok berlaku. Gunakan tanggal pemeriksaan atau tanggal keputusan penyesuaian.',
                        ])
                        <input type="date" name="adjustment_date" class="form-control" value="{{ old('adjustment_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        @include('shared.accounting.field-label', [
                            'label' => 'Reason Code',
                            'required' => true,
                            'tooltip' => 'Kode singkat alasan penyesuaian, misalnya manual_correction atau damaged_goods. Berguna untuk pelacakan dan laporan.',
                        ])
                        <input type="text" name="reason_code" class="form-control" value="{{ old('reason_code', 'manual_correction') }}" required>
                    </div>
                    <div class="col-12">
                        @include('shared.accounting.field-label', [
                            'label' => 'Reason',
                            'required' => true,
                            'tooltip' => 'Jelaskan alasan penyesuaian stok secara operasional, misalnya selisih opname atau barang rusak.',
                        ])
                        <textarea name="reason_text" class="form-control" rows="4" required>{{ old('reason_text') }}</textarea>
                    </div>
                    <div class="col-12">
                        @include('shared.accounting.field-label', [
                            'label' => 'Notes',
                            'tooltip' => 'Catatan tambahan untuk dokumen adjustment ini. Boleh dikosongkan jika alasan sudah cukup jelas.',
                        ])
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Items</h3>
                        <div class="text-muted small">Pilih produk, arah, dan qty.</div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-item-row">Tambah Item</button>
                </div>
                <div class="card-body" id="item-rows" data-product-options='@json($productOptions)'>
                    @php
                        $oldItems = old('items', [['direction' => 'in']]);
                    @endphp
                    @foreach($oldItems as $index => $oldItem)
                        <div class="row g-2 align-items-end item-row mb-2">
                            <div class="col-md-4">
                                <label class="form-label">Produk</label>
                                <select name="items[{{ $index }}][product_id]" class="form-select product-select" required>
                                    <option value="">Pilih produk</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected((string) ($oldItem['product_id'] ?? '') === (string) $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Variant</label>
                                <select name="items[{{ $index }}][product_variant_id]" class="form-select variant-select" data-selected="{{ $oldItem['product_variant_id'] ?? '' }}">
                                    <option value="">Produk utama</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                @include('shared.accounting.field-label', [
                                    'label' => 'Direction',
                                    'required' => true,
                                    'tooltip' => 'Tambah untuk menaikkan stok, Kurang untuk menurunkan stok. Pilih sesuai hasil pemeriksaan fisik.',
                                ])
                                <select name="items[{{ $index }}][direction]" class="form-select" required>
                                    <option value="in" @selected(($oldItem['direction'] ?? 'in') === 'in')>Tambah</option>
                                    <option value="out" @selected(($oldItem['direction'] ?? '') === 'out')>Kurang</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                @include('shared.accounting.field-label', [
                                    'label' => 'Qty',
                                    'required' => true,
                                    'tooltip' => 'Jumlah stok yang akan ditambah atau dikurangi pada item ini.',
                                ])
                                <input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $oldItem['quantity'] ?? '' }}" required>
                            </div>
                            <div class="col-md-10">
                                <label class="form-label">Item Notes</label>
                                <input type="text" name="items[{{ $index }}][notes]" class="form-control" value="{{ $oldItem['notes'] ?? '' }}" placeholder="Opsional">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger w-100 remove-item-row">Hapus</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Simpan Draft
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const container = document.getElementById('item-rows');
    const addButton = document.getElementById('add-item-row');
    const productOptions = JSON.parse(container?.dataset.productOptions || '{}');

    const productOptionMarkup = (selectedValue = '') => {
        return Object.entries(productOptions).map(([value, config]) => {
            const selected = String(selectedValue) === String(value) ? 'selected' : '';
            return `<option value="${value}" ${selected}>${config.label}</option>`;
        }).join('');
    };

    const syncVariantOptions = (row) => {
        const productSelect = row.querySelector('.product-select');
        const variantSelect = row.querySelector('.variant-select');
        const variants = productOptions[productSelect?.value]?.variants || [];
        const selectedVariant = variantSelect.dataset.selected || '';

        variantSelect.innerHTML = '<option value="">Produk utama</option>';
        variants.forEach((variant) => {
            const option = document.createElement('option');
            option.value = variant.id;
            option.textContent = variant.label;
            option.selected = String(selectedVariant) === String(variant.id);
            variantSelect.appendChild(option);
        });
    };

    const reindex = () => {
        container.querySelectorAll('.item-row').forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`);
            });
        });
    };

    const initializeRow = (row) => {
        syncVariantOptions(row);
    };

    addButton?.addEventListener('click', () => {
        const index = container.querySelectorAll('.item-row').length;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-end item-row mb-2';
        row.innerHTML = `
            <div class="col-md-4">
                <label class="form-label">Produk</label>
                <select name="items[${index}][product_id]" class="form-select product-select" required>
                    <option value="">Pilih produk</option>
                    ${productOptionMarkup()}
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Variant</label>
                <select name="items[${index}][product_variant_id]" class="form-select variant-select" data-selected="">
                    <option value="">Produk utama</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Arah</label>
                <select name="items[${index}][direction]" class="form-select" required>
                    <option value="in">Tambah</option>
                    <option value="out">Kurang</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Qty</label>
                <input type="number" step="0.0001" min="0.0001" name="items[${index}][quantity]" class="form-control" required>
            </div>
            <div class="col-md-10">
                <label class="form-label">Item Notes</label>
                <input type="text" name="items[${index}][notes]" class="form-control" placeholder="Opsional">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger w-100 remove-item-row">Hapus</button>
            </div>
        `;
        container.appendChild(row);
        initializeRow(row);
        reindex();
    });

    container?.addEventListener('change', (event) => {
        if (!event.target.classList.contains('product-select')) {
            return;
        }

        const row = event.target.closest('.item-row');
        const variantSelect = row.querySelector('.variant-select');
        variantSelect.dataset.selected = '';
        syncVariantOptions(row);
    });

    container?.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-item-row');
        if (!button) {
            return;
        }

        if (container.querySelectorAll('.item-row').length === 1) {
            return;
        }

        button.closest('.item-row')?.remove();
        reindex();
    });

    container?.querySelectorAll('.item-row').forEach(initializeRow);
})();
</script>
@endpush
