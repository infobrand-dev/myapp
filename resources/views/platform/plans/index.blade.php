@extends('layouts.platform')

@section('title', 'Katalog Plan')

@section('content')
    @php
        $storageFormatter = app(\App\Support\StorageSizeFormatter::class);
        $allModules = app(\App\Support\ModuleManager::class)->all();
    @endphp

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Platform Owner</div>
                <h2 class="page-title">Katalog Plan</h2>
                <p class="text-muted mb-0">Produk SaaS, feature flag, kuota, dan distribusi subscription.</p>
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
            @php
                $planFeatureKeys = array_keys(array_filter($plan->features ?? []));
                $unlockedModules = collect($allModules)->filter(function ($module) use ($planFeatureKeys) {
                    $req = \App\Support\PlanFeature::moduleFeatureRequirement((string) ($module['slug'] ?? ''));
                    $allReq = (array) ($req['all'] ?? []);
                    $anyReq = (array) ($req['any'] ?? []);
                    if ($allReq !== []) {
                        return count(array_intersect($planFeatureKeys, $allReq)) === count($allReq);
                    }
                    if ($anyReq !== []) {
                        return count(array_intersect($planFeatureKeys, $anyReq)) > 0;
                    }
                    return false;
                })->values();
            @endphp
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="card-title mb-0">{{ $plan->display_name }}</h3>
                            <div class="text-muted small">{{ $plan->code }} · {{ $plan->billing_interval ?: 'custom' }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if(($plan->meta['plan_revision'] ?? null) === 'v2')
                                <span class="badge bg-blue-lt text-blue">V2</span>
                            @elseif(($plan->meta['sales_status'] ?? null) === 'legacy')
                                <span class="badge bg-orange-lt text-orange">Legacy</span>
                            @endif
                            <span class="badge {{ $plan->is_public ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                {{ $plan->is_public ? 'Public' : 'Internal' }}
                            </span>
                            <a href="{{ route('platform.plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="ti ti-pencil me-1"></i>Edit
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-sm-4">
                                <div class="text-secondary text-uppercase small fw-bold mb-1">Harga Plan</div>
                                <div class="fs-4 fw-bold">
                                    @if(isset($plan->meta['price']))
                                        Rp {{ number_format((float) $plan->meta['price'], 0, ',', '.') }}
                                    @else
                                        <span class="text-muted">Belum diatur</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-secondary text-uppercase small fw-bold mb-1">POS Add-on</div>
                                <div class="fs-5 fw-semibold">
                                    @if(($plan->productLine() === 'accounting') && data_get($plan->meta, 'addons.point_of_sale.price') !== null)
                                        Rp {{ number_format((float) data_get($plan->meta, 'addons.point_of_sale.price', 0), 0, ',', '.') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-secondary text-uppercase small fw-bold mb-1">Subscriptions</div>
                                <div class="fs-4 fw-bold">{{ $plan->subscriptions_count }}</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="text-secondary text-uppercase small fw-bold mb-2">Fitur</div>
                                <div class="d-flex flex-column gap-1">
                                    @foreach($featureLabels as $key => $label)
                                        <div class="d-flex justify-content-between small">
                                            <span>{{ $label }}</span>
                                            <span class="badge {{ !empty(($plan->features ?? [])[$key]) ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                                {{ !empty(($plan->features ?? [])[$key]) ? 'On' : 'Off' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-secondary text-uppercase small fw-bold mb-2">Batas Kuota</div>
                                <div class="d-flex flex-column gap-1">
                                    @foreach($limitLabels as $key => $label)
                                        @php
                                            $limitValue = ($plan->limits ?? [])[$key] ?? null;
                                            $displayValue = $limitValue === null
                                                ? '∞'
                                                : ($key === \App\Support\PlanLimit::TOTAL_STORAGE_BYTES ? $storageFormatter->format((int) $limitValue) : $limitValue);
                                        @endphp
                                        <div class="d-flex justify-content-between small">
                                            <span>{{ $label }}</span>
                                            <span class="{{ $limitValue === null ? 'text-azure' : '' }}">{{ $displayValue }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Modules aktif --}}
                        <div class="border-top pt-3">
                            <div class="text-secondary text-uppercase small fw-bold mb-2">
                                Modules Aktif <span class="badge bg-secondary-lt text-secondary ms-1">{{ $unlockedModules->count() }}</span>
                            </div>
                            @if($unlockedModules->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($unlockedModules as $mod)
                                        <span class="badge bg-blue-lt text-blue">{{ $mod['name'] }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted small">Tidak ada module yang terkunci ke fitur plan ini.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
