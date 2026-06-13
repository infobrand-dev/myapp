@extends('layouts.platform')

@section('title', 'Buat Plan')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Platform Owner · Katalog Plan</div>
                <h2 class="page-title">Buat Plan</h2>
                <p class="text-muted mb-0">Gunakan template jika ingin mulai dari preset lalu sesuaikan fitur dan limitnya.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Katalog Plan
                </a>
            </div>
        </div>
    </div>

    @include('platform.plans.partials.form', [
        'submitRoute' => route('platform.plans.store'),
        'method' => 'POST',
        'submitLabel' => 'Buat Plan',
    ])
@endsection
