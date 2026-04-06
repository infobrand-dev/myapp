@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('content')
    @php
        $storageFormatter = app(\App\Support\StorageSizeFormatter::class);

        $limitGroups = [
            'Workspace' => [
                \App\Support\PlanLimit::COMPANIES,
                \App\Support\PlanLimit::BRANCHES,
                \App\Support\PlanLimit::USERS,
                \App\Support\PlanLimit::PRODUCTS,
                \App\Support\PlanLimit::CONTACTS,
                \App\Support\PlanLimit::TOTAL_STORAGE_BYTES,
            ],
            'Channel' => [
                \App\Support\PlanLimit::WHATSAPP_INSTANCES,
                \App\Support\PlanLimit::SOCIAL_ACCOUNTS,
                \App\Support\PlanLimit::LIVE_CHAT_WIDGETS,
                \App\Support\PlanLimit::EMAIL_INBOX_ACCOUNTS,
            ],
            'Pesan & Blast' => [
                \App\Support\PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY,
                \App\Support\PlanLimit::EMAIL_CAMPAIGNS,
                \App\Support\PlanLimit::EMAIL_RECIPIENTS_MONTHLY,
            ],
            'AI & Chatbot' => [
                \App\Support\PlanLimit::AI_CREDITS_MONTHLY,
                \App\Support\PlanLimit::CHATBOT_ACCOUNTS,
                \App\Support\PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS,
                \App\Support\PlanLimit::BYO_CHATBOT_ACCOUNTS,
                \App\Support\PlanLimit::BYO_AI_REQUESTS_MONTHLY,
                \App\Support\PlanLimit::BYO_AI_TOKENS_MONTHLY,
            ],
            'Automation' => [
                \App\Support\PlanLimit::AUTOMATION_WORKFLOWS,
                \App\Support\PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY,
            ],
        ];
    @endphp

    {{-- Page Header --}}
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Platform Owner · Katalog Plan</div>
                <h2 class="page-title">Edit Plan</h2>
                <p class="text-muted mb-0">{{ $plan->display_name }} · <code>{{ $plan->code }}</code>
                    @if(($plan->meta['plan_revision'] ?? null) === 'v2')
                        <span class="badge bg-blue-lt text-blue ms-1">V2</span>
                    @endif
                    @if(($plan->meta['sales_status'] ?? null) === 'legacy')
                        <span class="badge bg-orange-lt text-orange ms-1">Legacy</span>
                    @endif
                </p>
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

        {{-- Preset Templates --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-bolt me-1 text-muted"></i>Isi Cepat via Template
                </h3>
                <div class="card-options">
                    <span class="text-muted small">Klik template untuk mengisi field Fitur &amp; Batas secara otomatis. Nilai tetap bisa diubah manual.</span>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2" id="preset-buttons">
                    @foreach($planPresets as $presetKey => $preset)
                        <button type="button"
                            class="btn btn-outline-secondary js-plan-preset"
                            data-preset-key="{{ $presetKey }}"
                            data-preset='@json($preset)'
                            title="{{ $preset['description'] }}">
                            <span class="fw-semibold">{{ $preset['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">

            {{-- Kolom 1: Identitas --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Identitas</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Kode</label>
                                <input type="text" class="form-control" value="{{ $plan->code }}" disabled>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $plan->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Product Line</label>
                                <select name="product_line" class="form-select @error('product_line') is-invalid @enderror">
                                    <option value="">Tanpa kategori khusus</option>
                                    @foreach($productLineOptions as $key => $label)
                                        <option value="{{ $key }}" @selected(old('product_line', $plan->productLine()) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('product_line') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-6">
                                <label class="form-label">Siklus Tagihan</label>
                                <input type="text" name="billing_interval"
                                    class="form-control @error('billing_interval') is-invalid @enderror"
                                    value="{{ old('billing_interval', $plan->billing_interval) }}"
                                    placeholder="monthly / yearly">
                                @error('billing_interval') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-6">
                                <label class="form-label">Urutan Tampil</label>
                                <input type="number" name="sort_order"
                                    class="form-control @error('sort_order') is-invalid @enderror"
                                    value="{{ old('sort_order', $plan->sort_order) }}">
                                @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                @php $isAccountingPlan = old('product_line', $plan->productLine()) === 'accounting'; @endphp
                                <label class="form-label">Harga POS Add-on</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="point_of_sale_addon_price"
                                        class="form-control @error('point_of_sale_addon_price') is-invalid @enderror"
                                        value="{{ old('point_of_sale_addon_price', data_get($plan->meta, 'addons.point_of_sale.price')) }}"
                                        min="0" step="1" placeholder="0">
                                </div>
                                @error('point_of_sale_addon_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if(!$isAccountingPlan)
                                    <div class="form-hint">Hanya berlaku jika product line = Accounting.</div>
                                @endif
                            </div>

                            <div class="col-12">
                                <label class="form-check mb-2">
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

            {{-- Kolom 2: Fitur --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Fitur</h3>
                    </div>
                    <div class="card-body">
                        @foreach($featureLabels as $key => $label)
                            <label class="form-check mb-2">
                                <input type="checkbox" class="form-check-input"
                                    name="features[{{ $key }}]" value="1"
                                    @checked(old('features.' . $key, ($plan->features ?? [])[$key] ?? false))>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Kolom 3: Batas Kuota (dikelompokkan) --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Batas Kuota</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-hint mb-3">Kosong = tidak terbatas.</div>

                        @foreach($limitGroups as $groupName => $groupKeys)
                            <div class="text-uppercase text-muted small fw-bold mb-2 {{ !$loop->first ? 'mt-3' : '' }}">{{ $groupName }}</div>
                            @foreach($groupKeys as $key)
                                @if(isset($limitLabels[$key]))
                                    <div class="mb-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text text-muted" style="min-width:140px; font-size:.75rem;">{{ $limitLabels[$key] }}</span>
                                            <input type="number" class="form-control form-control-sm"
                                                name="limits[{{ $key }}]"
                                                value="{{ old('limits.' . $key, ($plan->limits ?? [])[$key] ?? '') }}"
                                                placeholder="∞">
                                        </div>
                                        @if($key === \App\Support\PlanLimit::TOTAL_STORAGE_BYTES)
                                            <div class="form-hint" style="font-size:.7rem;">
                                                1 GB = <code>1073741824</code> · 5 GB = <code>5368709120</code> · 20 GB = <code>21474836480</code>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        @endforeach
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
            const presetButtons = document.querySelectorAll('.js-plan-preset');

            presetButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const preset = JSON.parse(button.getAttribute('data-preset') || '{}');

                    // Highlight active preset
                    presetButtons.forEach(function (b) {
                        b.classList.remove('btn-primary');
                        b.classList.add('btn-outline-secondary');
                    });
                    button.classList.remove('btn-outline-secondary');
                    button.classList.add('btn-primary');

                    // Apply product_line
                    if (preset.product_line !== undefined) {
                        const productLine = document.querySelector('select[name="product_line"]');
                        if (productLine) productLine.value = preset.product_line;
                    }

                    // Apply POS add-on price from meta
                    const addonInput = document.querySelector('input[name="point_of_sale_addon_price"]');
                    if (addonInput) {
                        addonInput.value = preset.meta?.addons?.point_of_sale?.price ?? '';
                    }

                    // Apply features
                    Object.entries(preset.features || {}).forEach(function ([key, enabled]) {
                        const input = document.querySelector('input[name="features[' + key + ']"]');
                        if (input) input.checked = !!enabled;
                    });

                    // Apply limits
                    Object.entries(preset.limits || {}).forEach(function ([key, value]) {
                        const input = document.querySelector('input[name="limits[' + key + ']"]');
                        if (input) input.value = (value === 0 || value === null || value === undefined) ? '' : value;
                    });

                    if (window.AppToast) {
                        window.AppToast.info('Template "' + button.querySelector('.fw-semibold').textContent + '" diterapkan.');
                    }
                });
            });
        });
    </script>
@endsection
