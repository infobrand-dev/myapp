@extends('layouts.admin')

@section('title', 'Import Opening Stock')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori · Opening Stock</div>
            <h2 class="page-title">Import Opening Stock</h2>
            <p class="text-muted mb-0">Upload file CSV atau XLSX untuk posting stok awal massal.</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('inventory.openings.import-template', 'csv') }}" class="btn btn-outline-secondary">Template CSV</a>
            <a href="{{ route('inventory.openings.import-template', 'xlsx') }}" class="btn btn-outline-secondary">Template XLSX</a>
            <a href="{{ route('inventory.openings.index') }}" class="btn btn-outline-secondary">Kembali</a>
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
        <form method="POST" action="{{ route('inventory.openings.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header"><h3 class="card-title">Upload File</h3></div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <select name="inventory_location_id" class="form-select" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Opening Date</label>
                        <input type="date" name="opening_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">Import opening stock</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">File Import</label>
                        <input type="file" name="import_file" class="form-control @error('import_file') is-invalid @enderror" accept=".csv,.txt,.xlsx" required>
                        <div class="form-hint">Kolom utama: sku atau product_name, quantity, minimum_quantity, reorder_quantity.</div>
                        @error('import_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('inventory.openings.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button class="btn btn-primary">Import Opening</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kolom Template</h3></div>
            <div class="card-body">
                <div class="small text-muted" style="line-height:1.8;">
                    <code>sku</code>, <code>variant_sku</code>, <code>product_name</code>, <code>quantity</code>,
                    <code>minimum_quantity</code>, <code>reorder_quantity</code>, <code>notes</code>
                </div>
                <div class="mt-3 text-muted small">
                    Jika <code>variant_sku</code> kosong, stok awal diposting ke produk utama.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
