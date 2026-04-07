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
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="transaction_type" class="form-select @error('transaction_type') is-invalid @enderror" required>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('transaction_type', $category->transaction_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('transaction_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                        <span class="form-check-label">Active</span>
                    </label>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4">{{ old('notes', $category->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
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
