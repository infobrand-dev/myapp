@extends('layouts.admin')

@section('title', 'Plan Catalog')

@section('content')
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">Katalog Plan</h1>
            <div class="text-muted small mt-1">Lihat produk SaaS, feature flag, kuota, dan distribusi subscription.</div>
        </div>
        <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <div class="row g-3">
        @foreach($plans as $plan)
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="card-title mb-0">{{ $plan->name }}</h3>
                            <div class="text-muted small">{{ $plan->code }} · {{ $plan->billing_interval ?: 'custom' }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $plan->is_public ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">
                                {{ $plan->is_public ? 'Public' : 'Internal' }}
                            </span>
                            <a href="{{ route('platform.plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </div>
                    </div>
                    <div class="card-body">
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
                                        <div class="d-flex justify-content-between small">
                                            <span>{{ $label }}</span>
                                            <span>{{ ($plan->limits ?? [])[$key] ?? 'Unlimited' }}</span>
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
