@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('content')
    @php
        $storageFormatter = app(\App\Support\StorageSizeFormatter::class);
    @endphp
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">Edit Plan</h1>
            <div class="text-muted small mt-1">{{ $plan->display_name }} · {{ $plan->code }}</div>
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

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">Identitas</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Kode</label>
                            <input type="text" class="form-control" value="{{ $plan->code }}" disabled>
                        </div>
                        @if(($plan->meta['plan_revision'] ?? null) || ($plan->meta['sales_status'] ?? null))
                            <div class="mb-3">
                                <label class="form-label">Status Katalog</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @if(($plan->meta['plan_revision'] ?? null) === 'v2')
                                        <span class="badge bg-primary-lt text-primary">Public revision V2</span>
                                    @endif
                                    @if(($plan->meta['sales_status'] ?? null) === 'legacy')
                                        <span class="badge bg-warning-lt text-warning">Legacy sales plan</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $plan->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Line</label>
                            <select class="form-select" name="product_line">
                                <option value="">Tanpa kategori khusus</option>
                                @foreach($productLineOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('product_line', $plan->productLine()) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint">Gunakan ini untuk membedakan lini produk seperti Omnichannel, CRM, Accounting, atau Project Management.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Siklus Tagihan</label>
                            <input type="text" class="form-control" name="billing_interval" value="{{ old('billing_interval', $plan->billing_interval) }}" placeholder="monthly / yearly / custom">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan Tampil</label>
                            <input type="number" class="form-control" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}">
                        </div>
                        @php
                            $isAccountingPlan = old('product_line', $plan->productLine()) === 'accounting';
                        @endphp
                        <div class="mb-3">
                            <label class="form-label">Harga POS Add-on</label>
                            <input type="number" class="form-control" name="point_of_sale_addon_price" value="{{ old('point_of_sale_addon_price', data_get($plan->meta, 'addons.point_of_sale.price')) }}" min="0" step="0.01">
                            <div class="form-hint">
                                @if($isAccountingPlan)
                                    Dipakai sebagai harga default POS Add-on saat admin membuat order billing untuk plan ini.
                                @else
                                    Nilai ini hanya dipakai jika product line plan adalah Accounting.
                                @endif
                            </div>
                        </div>
                        <label class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>
                            <span class="form-check-label">Plan aktif</span>
                        </label>
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_public" value="1" @checked(old('is_public', $plan->is_public))>
                            <span class="form-check-label">Tampil di katalog publik</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">Fitur & Batas</h3></div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label">Template Paket</label>
                            <div class="row g-2">
                                @foreach($planPresets as $presetKey => $preset)
                                    <div class="col-md-6">
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary text-start w-100 h-100 js-plan-preset"
                                            data-preset='@json($preset)'
                                        >
                                            <div class="fw-semibold">{{ $preset['label'] }}</div>
                                            <div class="small text-muted">{{ $preset['description'] }}</div>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-hint mt-2">Template ini hanya mengisi default feature dan limit. Anda tetap bisa mengubah manual setelahnya.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-secondary text-uppercase small fw-bold mb-2">Fitur</div>
                                @foreach($featureLabels as $key => $label)
                                    <label class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" name="features[{{ $key }}]" value="1" @checked(old('features.' . $key, ($plan->features ?? [])[$key] ?? false))>
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="col-md-6">
                                <div class="text-secondary text-uppercase small fw-bold mb-2">Batas Kuota</div>
                                @foreach($limitLabels as $key => $label)
                                    <div class="mb-2">
                                        <label class="form-label">{{ $label }}</label>
                                        <input type="number" class="form-control" name="limits[{{ $key }}]" value="{{ old('limits.' . $key, ($plan->limits ?? [])[$key] ?? null) }}" placeholder="Kosong = tidak terbatas">
                                        @if($key === \App\Support\PlanLimit::TOTAL_STORAGE_BYTES)
                                            <div class="form-hint">Contoh: 1073741824 = {{ $storageFormatter->format(1073741824) }}, 5368709120 = {{ $storageFormatter->format(5368709120) }}.</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan Plan
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-plan-preset').forEach(function (button) {
                button.addEventListener('click', function () {
                    const preset = JSON.parse(button.getAttribute('data-preset') || '{}');

                    if (preset.product_line) {
                        const productLine = document.querySelector('select[name="product_line"]');
                        if (productLine) {
                            productLine.value = preset.product_line;
                        }
                    }

                    if (preset.meta && preset.meta.addons && preset.meta.addons.point_of_sale) {
                        const addonInput = document.querySelector('input[name="point_of_sale_addon_price"]');
                        if (addonInput) {
                            addonInput.value = preset.meta.addons.point_of_sale.price ?? '';
                        }
                    }

                    Object.entries(preset.features || {}).forEach(function ([key, enabled]) {
                        const input = document.querySelector('input[name="features[' + key + ']"]');
                        if (input) {
                            input.checked = !!enabled;
                        }
                    });

                    Object.entries(preset.limits || {}).forEach(function ([key, value]) {
                        const input = document.querySelector('input[name="limits[' + key + ']"]');
                        if (input) {
                            input.value = value;
                        }
                    });
                });
            });
        });
    </script>
@endsection
