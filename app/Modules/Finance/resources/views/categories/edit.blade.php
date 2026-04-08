@extends('layouts.admin')

@section('title', 'Edit Kategori Finance')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Edit Kategori Finance</h2>
            <p class="text-muted mb-0">Edit kategori transaksi keuangan.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('finance.categories.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('finance.categories.update', $category) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Kategori</h3>
        </div>
        <div class="card-body">
            @include('finance::categories.partials.form', [
                'category' => $category,
                'typeOptions' => $typeOptions,
                'notesRows' => 4,
            ])
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('finance.categories.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>

@endsection
