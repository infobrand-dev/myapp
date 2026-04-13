@extends('layouts.admin')

@section('title', 'Import Products')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Catalog · Products</div>
            <h2 class="page-title">Import Products</h2>
            <p class="text-muted mb-0">Upload file CSV atau XLSX untuk import master product.</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('products.import-template', 'csv') }}" class="btn btn-outline-secondary">Template CSV</a>
            <a href="{{ route('products.import-template', 'xlsx') }}" class="btn btn-outline-secondary">Template XLSX</a>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>
</div>

@if(session('import_skipped'))
    <div class="alert alert-warning">
        <strong>Baris yang dilewati:</strong>
        <ul class="mb-0 mt-2">
            @foreach(collect(session('import_skipped'))->take(10) as $rowError)
                <li>{{ $rowError }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header"><h3 class="card-title">Upload File</h3></div>
                <div class="card-body">
                    <label class="form-label">File Import</label>
                    <input type="file" name="import_file" class="form-control @error('import_file') is-invalid @enderror" accept=".csv,.txt,.xlsx" required>
                    <div class="form-hint">Format: CSV atau XLSX. Maks 10 MB.</div>
                    @error('import_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button class="btn btn-primary">Import Products</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kolom Template</h3></div>
            <div class="card-body">
                <div class="small text-muted" style="line-height:1.8;">
                    <code>type</code>, <code>name</code>, <code>sku</code>, <code>barcode</code>,
                    <code>category</code>, <code>brand</code>, <code>unit</code>, <code>supplier</code>,
                    <code>cost_price</code>, <code>sell_price</code>, <code>minimum_stock</code>,
                    <code>reorder_point</code>, <code>is_active</code>, <code>track_stock</code>, <code>description</code>
                </div>
                <div class="mt-3 text-muted small">
                    Jika SKU cocok dengan produk lama, row akan memperbarui produk tersebut. Jika belum ada, produk baru akan dibuat.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
