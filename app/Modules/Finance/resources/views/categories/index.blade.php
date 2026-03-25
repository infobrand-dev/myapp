@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Finance Categories</h2>
        <div class="text-muted small">Category untuk cash in, cash out, dan expense operasional ringan.</div>
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

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Create Category</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.categories.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Type</label>
                        <select name="transaction_type" class="form-select" required>
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('transaction_type', 'expense') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', true))>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Transactions</th><th></th></tr></thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->transaction_type }}</td>
                                <td>{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                                <td>{{ $category->transactions_count }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('finance.categories.edit', $category) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                        @if($category->transactions_count === 0)
                                            <form method="POST" action="{{ route('finance.categories.destroy', $category) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Yakin ingin menghapus category ini?">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada category.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
