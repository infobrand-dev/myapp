@extends('layouts.admin')

@section('title', 'Plan Catalog')

@section('content')
    @php
        $storageFormatter = app(\App\Support\StorageSizeFormatter::class);
    @endphp
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">Katalog Plan</h1>
            <div class="text-muted small mt-1">Lihat produk SaaS, feature flag, kuota, dan distribusi subscription.</div>
            </div>
            <div class="col-auto">
        <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Dashboard
        </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach($plans as $plan)
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="card-title mb-0">{{ $plan->display_name }}</h3>
                            <div class="text-muted small">{{ $plan->code }} · {{ $plan->billing_interval ?: 'custom' }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if(($plan->meta['plan_revision'] ?? null) === 'v2')
                                <span class="badge bg-primary-lt text-primary">V2</span>
                            @elseif(($plan->meta['sales_status'] ?? null) === 'legacy')
                                <span class="badge bg-warning-lt text-warning">Legacy</span>
                            @endif
                            <span class="badge {{ $plan->is_public ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">
                                {{ $plan->is_public ? 'Public' : 'Internal' }}
                            </span>
                            <a href="{{ route('platform.plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="text-secondary text-uppercase small fw-bold mb-1">Harga Plan</div>
                                <div class="fs-4 fw-bold">
                                    @if(isset($plan->meta['price']))
                                        Rp {{ number_format((float) $plan->meta['price'], 0, ',', '.') }}
                                    @else
                                        <span class="text-muted">Belum diatur</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-secondary text-uppercase small fw-bold mb-1">POS Add-on</div>
                                <div class="fs-5 fw-semibold">
                                    @if(($plan->productLine() === 'accounting') && data_get($plan->meta, 'addons.point_of_sale.price') !== null)
                                        Rp {{ number_format((float) data_get($plan->meta, 'addons.point_of_sale.price', 0), 0, ',', '.') }}
                                    @else
                                        <span class="text-muted">Tidak dipakai</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-secondary text-uppercase small fw-bold mb-2">Subscriptions Aktif</div>
                        <div class="fs-2 fw-bold mb-4">{{ $plan->subscriptions_count }}</div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-secondary text-uppercase small fw-bold mb-2">Fitur</div>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($featureLabels as $key => $label)
                                        <div class="d-flex justify-content-between small">
                                            <span>{{ $label }}</span>
                                            <span class="badge {{ !empty(($plan->features ?? [])[$key]) ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">
                                                {{ !empty(($plan->features ?? [])[$key]) ? 'On' : 'Off' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-secondary text-uppercase small fw-bold mb-2">Batas Kuota</div>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($limitLabels as $key => $label)
                                        @php
                                            $limitValue = ($plan->limits ?? [])[$key] ?? null;
                                            $displayValue = $limitValue === null
                                                ? 'Unlimited'
                                                : ($key === \App\Support\PlanLimit::TOTAL_STORAGE_BYTES ? $storageFormatter->format((int) $limitValue) : $limitValue);
                                        @endphp
                                        <div class="d-flex justify-content-between small">
                                            <span>{{ $label }}</span>
                                            <span>{{ $displayValue }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
