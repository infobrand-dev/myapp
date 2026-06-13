@extends('layouts.platform')

@section('title', 'Edit Plan')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Platform Owner · Katalog Plan</div>
                <h2 class="page-title">Edit Plan</h2>
                <p class="text-muted mb-0">
                    {{ $plan->display_name }} · <code>{{ $plan->code }}</code>
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

    @include('platform.plans.partials.form', [
        'submitRoute' => route('platform.plans.update', $plan),
        'method' => 'PUT',
        'submitLabel' => 'Simpan Plan',
    ])
@endsection
