@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Edit Finance Category</h2>
    <div class="text-muted small">Edit kategori transaksi keuangan.</div>
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

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('finance.categories.update', $category) }}" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Type</label>
                <select name="transaction_type" class="form-select" required>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('transaction_type', $category->transaction_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                    <span class="form-check-label">Active</span>
                </label>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="4">{{ old('notes', $category->notes) }}</textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Update Category</button>
                <a href="{{ route('finance.categories.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
