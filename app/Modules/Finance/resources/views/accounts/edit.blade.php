@extends('layouts.admin')

@section('title', 'Edit Finance Account')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Edit Finance Account</h2>
            <p class="text-muted mb-0">{{ $account->name }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('finance.accounts.index') }}" class="btn btn-outline-secondary">
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

<form method="POST" action="{{ route('finance.accounts.update', $account) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Account</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $account->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('account_type', $account->account_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $account->slug) }}">
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Number / Reference</label>
                    <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number', $account->account_number) }}">
                    @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $account->is_active))>
                        <span class="form-check-label">Active</span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_default" value="1" @checked(old('is_default', $account->is_default))>
                        <span class="form-check-label">Jadikan default account</span>
                    </label>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4">{{ old('notes', $account->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('finance.accounts.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>
@endsection
