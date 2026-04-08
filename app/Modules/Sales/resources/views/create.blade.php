@extends('layouts.admin')

@section('title', 'Create Sale')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">Create Sale</h2>
            <p class="text-muted mb-0">Create a new sales transaction.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>
</div>

@include('sales::partials.form', [
    'submitRoute' => route('sales.store'),
    'method'      => 'POST',
])
@endsection
