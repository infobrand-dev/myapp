@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('content')
    @php
        $storageFormatter = app(\App\Support\StorageSizeFormatter::class);
    @endphp

    {{-- Page Header --}}
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Platform Owner · Katalog Plan</div>
                <h2 class="page-title">Edit Plan</h2>
                <p class="text-muted mb-0">{{ $plan->display_name }} <span class="text-muted">·</span> <code>{{ $plan->code }}</code></p>
            </div>
            <div class="col-auto">
                <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Katalog Plan
                </a>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('platform.plans.update', $plan) }}">
        @csrf
        @method('PUT')

        <div class="row g-3 mb-3">

            {{-- Kolom Kiri: Identitas --}}
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Identitas Plan</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            {{-- Kode (read-only) --}}
                            <div class="col-12">
                                <label class="form-label">Kode</label>
                                <input type="text" class="form-control" value="{{ $plan->code }}" disabled>
                                <div class="form-hint">Kode tidak dapat diubah.</div>
                            </div>

                            {{-- Status Katalog --}}
                            @if(($plan->meta['plan_revision'] ?? null) || ($plan->meta['sales_status'] ?? null))
                                <div class="col-12">
                                    <label class="form-label">Status Katalog</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if(($plan->meta['plan_revision'] ?? null) === 'v2')
                                            <span class="badge bg-blue-lt text-blue">Public revision V2</span>
                                        @endif
                                        @if(($plan->meta['sales_status'] ?? null) === 'legacy')
                                            <span class="badge bg-orange-lt text-orange">Legacy sales plan</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Nama --}}
                            <div class="col-12">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $plan->name) }}"
                                    required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Product Line --}}
                            <div class="col-12">
                                <label class="form-label">Product Line</label>
                                <select name="product_line" class="form-select @error('product_line') is-invalid @enderror">
                                    <option value="">Tanpa kategori khusus</option>
                                    @foreach($productLineOptions as $key => $label)
                                        <option value="{{ $key }}" @selected(old('product_line', $plan->productLine()) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('product_line')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Membedakan lini produk: Omnichannel, CRM, Accounting, dll.</div>
                            </div>

                            {{-- Siklus Tagihan --}}
                            <div class="col-md-6">
                                <label class="form-label">Siklus Tagihan</label>
                                <input type="text" name="billing_interval"
                                    class="form-control @error('billing_interval') is-invalid @enderror"
                                    value="{{ old('billing_interval', $plan->billing_interval) }}"
                                    placeholder="monthly / yearly / custom">
                                @error('billing_interval')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Urutan Tampil --}}
                            <div class="col-md-6">
                                <label class="form-label">Urutan Tampil</label>
                                <input type="number" name="sort_order"
                                    class="form-control @error('sort_order') is-invalid @enderror"
                                    value="{{ old('sort_order', $plan->sort_order) }}">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- POS Add-on Price --}}
                            <div class="col-12">
                                @php $isAccountingPlan = old('product_line', $plan->productLine()) === 'accounting'; @endphp
                                <label class="form-label">Harga POS Add-on</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="point_of_sale_addon_price"
                                        class="form-control @error('point_of_sale_addon_price') is-invalid @enderror"
                                        value="{{ old('point_of_sale_addon_price', data_get($plan->meta, 'addons.point_of_sale.price')) }}"
                                        min="0" step="0.01" placeholder="0">
                                    @error('point_of_sale_addon_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-hint">
                                    @if($isAccountingPlan)
                                        Dipakai sebagai harga default POS Add-on untuk plan Accounting ini.
                                    @else
                                        Hanya berlaku jika product line adalah <strong>Accounting</strong>.
                                    @endif
                                </div>
                            </div>

                            {{-- Toggle Status --}}
                            <div class="col-12">
                                <div class="d-flex flex-column gap-2">
                                    <label class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_active" value="1"
                                            @checked(old('is_active', $plan->is_active))>
                                        <span class="form-check-label">Plan aktif</span>
                                    </label>
                                    <label class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_public" value="1"
                                            @checked(old('is_public', $plan->is_public))>
                                        <span class="form-check-label">Tampil di katalog publik</span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Fitur & Batas --}}
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Fitur &amp; Batas Kuota</h3>
                    </div>
                    <div class="card-body">

                        {{-- Preset Template --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Isi Cepat via Template</label>
                            <div class="row g-2">
                                @foreach($planPresets as $presetKey => $preset)
                                    <div class="col-md-6">
                                        <button type="button"
                                            class="btn btn-outline-secondary text-start w-100 h-100 js-plan-preset"
                                            data-preset='@json($preset)'>
                                            <div class="fw-semibold">{{ $preset['label'] }}</div>
                                            <div class="small text-muted">{{ $preset['description'] }}</div>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-hint mt-2">Template mengisi field di bawah secara otomatis. Nilai bisa diubah manual setelahnya.</div>
                        </div>

                        <hr class="my-3">

                        <div class="row g-4">
                            {{-- Fitur --}}
                            <div class="col-md-6">
                                <div class="text-uppercase text-muted small fw-bold mb-3">Fitur</div>
                                @foreach($featureLabels as $key => $label)
                                    <label class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input"
                                            name="features[{{ $key }}]" value="1"
                                            @checked(old('features.' . $key, ($plan->features ?? [])[$key] ?? false))>
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>

                            {{-- Batas Kuota --}}
                            <div class="col-md-6">
                                <div class="text-uppercase text-muted small fw-bold mb-3">Batas Kuota</div>
                                @foreach($limitLabels as $key => $label)
                                    <div class="mb-3">
                                        <label class="form-label">{{ $label }}</label>
                                        <input type="number" class="form-control"
                                            name="limits[{{ $key }}]"
                                            value="{{ old('limits.' . $key, ($plan->limits ?? [])[$key] ?? null) }}"
                                            placeholder="Kosong = tidak terbatas">
                                        @if($key === \App\Support\PlanLimit::TOTAL_STORAGE_BYTES)
                                            <div class="form-hint">
                                                Contoh: 1 GB = <code>1073741824</code>, 5 GB = <code>5368709120</code>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- Footer Aksi --}}
        <div class="card">
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Simpan Plan
                </button>
            </div>
        </div>

    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-plan-preset').forEach(function (button) {
                button.addEventListener('click', function () {
                    const preset = JSON.parse(button.getAttribute('data-preset') || '{}');

                    if (preset.product_line) {
                        const productLine = document.querySelector('select[name="product_line"]');
                        if (productLine) productLine.value = preset.product_line;
                    }

                    if (preset.meta?.addons?.point_of_sale) {
                        const addonInput = document.querySelector('input[name="point_of_sale_addon_price"]');
                        if (addonInput) addonInput.value = preset.meta.addons.point_of_sale.price ?? '';
                    }

                    Object.entries(preset.features || {}).forEach(function ([key, enabled]) {
                        const input = document.querySelector('input[name="features[' + key + ']"]');
                        if (input) input.checked = !!enabled;
                    });

                    Object.entries(preset.limits || {}).forEach(function ([key, value]) {
                        const input = document.querySelector('input[name="limits[' + key + ']"]');
                        if (input) input.value = value;
                    });
                });
            });
        });
    </script>
@endsection
