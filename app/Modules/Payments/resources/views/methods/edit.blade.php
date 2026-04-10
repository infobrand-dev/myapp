@extends('layouts.admin')

@section('title', 'Edit Payment Method')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <div class="page-pretitle">Keuangan / Payment Methods</div>
        <h2 class="page-title">Edit Payment Method</h2>
    </div>
    <a href="{{ route('payments.methods.index') }}" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>Kembali
    </a>
</div>

<form method="POST" action="{{ route('payments.methods.update', $method) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $method->name }}</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @include('payments::partials.method-form', ['method' => $method])
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('payments.methods.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>
@endsection
