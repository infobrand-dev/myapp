@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Import Contacts</h2>
        <div class="text-muted small">Upload file CSV atau XLSX dengan header standar bahasa Inggris.</div>
    </div>
    <div class="btn-list">
        <a href="{{ route('contacts.import-template', 'csv') }}" class="btn btn-outline-secondary">Download Template CSV</a>
        <a href="{{ route('contacts.import-template', 'xlsx') }}" class="btn btn-outline-secondary">Download Template XLSX</a>
        <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

@if($errors->has('import_file'))
    <div class="alert alert-danger">{{ $errors->first('import_file') }}</div>
@endif

@if(session('import_skipped'))
    <div class="alert alert-warning">
        <div class="fw-semibold mb-1">Baris yang dilewati</div>
        <ul class="mb-0 ps-3">
            @foreach(collect(session('import_skipped'))->take(10) as $rowError)
                <li>{{ $rowError }}</li>
            @endforeach
        </ul>
        @if(count(session('import_skipped')) > 10)
            <div class="small text-muted mt-2">Menampilkan 10 error pertama dari {{ count(session('import_skipped')) }} baris yang dilewati.</div>
        @endif
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('contacts.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">File Import</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,.txt,.xlsx" required>
                        <div class="text-muted small mt-2">Format yang didukung: <code>.csv</code>, <code>.txt</code>, dan <code>.xlsx</code>. Ukuran maksimum 10 MB.</div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Import Contacts</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <div class="fw-semibold mb-2">Header Standar</div>
                <div class="text-muted small mb-2">Gunakan header template berikut agar proses import konsisten:</div>
                <div class="small">
                    <code>type</code>, <code>name</code>, <code>company_name</code>, <code>job_title</code>, <code>email</code>, <code>phone</code>, <code>mobile</code>, <code>website</code>, <code>vat</code>, <code>company_registry</code>, <code>industry</code>, <code>street</code>, <code>street2</code>, <code>city</code>, <code>state</code>, <code>zip</code>, <code>country</code>, <code>notes</code>, <code>is_active</code>
                </div>
                <hr>
                <div class="fw-semibold mb-2">Catatan</div>
                <div class="text-muted small">Kolom minimum yang wajib ada adalah <code>name</code>.</div>
                <div class="text-muted small mt-2">Untuk data individual yang terkait perusahaan, isi <code>company_name</code>. Jika company belum ada, sistem akan membuat contact company otomatis.</div>
                <div class="text-muted small mt-2">Saat ini sistem juga masih mencoba mengenali beberapa alias header umum, tetapi template standar tetap bahasa Inggris.</div>
            </div>
        </div>
    </div>
</div>
@endsection
