@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('content')
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">Edit Plan</h1>
            <div class="text-muted small mt-1">{{ $plan->display_name }} · {{ $plan->code }}</div>
        </div>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Katalog Plan
        </a>
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
                            <div class="form-hint">Gunakan ini untuk membedakan lini produk seperti Omnichannel, CRM, Commerce, atau Project Management.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Siklus Tagihan</label>
                            <input type="text" class="form-control" name="billing_interval" value="{{ old('billing_interval', $plan->billing_interval) }}" placeholder="monthly / yearly / custom">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan Tampil</label>
                            <input type="number" class="form-control" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}">
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
@endsection
