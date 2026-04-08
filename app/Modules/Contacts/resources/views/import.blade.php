@extends('layouts.admin')

@section('title', 'Import Contacts')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">CRM · Contacts</div>
            <h2 class="page-title">Import Contacts</h2>
            <p class="text-muted mb-0">Upload file CSV atau XLSX untuk import massal.</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('contacts.import-template', 'csv') }}" class="btn btn-outline-secondary">
                <i class="ti ti-file-type-csv me-1"></i>Template CSV
            </a>
            <a href="{{ route('contacts.import-template', 'xlsx') }}" class="btn btn-outline-secondary">
                <i class="ti ti-file-spreadsheet me-1"></i>Template XLSX
            </a>
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-azure alert-dismissible mb-3">
        <i class="ti ti-info-circle me-2"></i>{{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->has('import_file'))
    <div class="alert alert-danger alert-dismissible mb-3">
        <i class="ti ti-alert-circle me-2"></i>{{ $errors->first('import_file') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('import_skipped'))
    <div class="alert alert-warning alert-dismissible mb-3">
        <i class="ti ti-alert-triangle me-2"></i>
        <strong>Baris yang dilewati:</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach(collect(session('import_skipped'))->take(10) as $rowError)
                <li>{{ $rowError }}</li>
            @endforeach
        </ul>
        @if(count(session('import_skipped')) > 10)
            <div class="text-muted small mt-2">Menampilkan 10 dari {{ count(session('import_skipped')) }} baris yang dilewati.</div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@include('shared.plan-limit-alert', [
    'state' => $contactLimitState,
    'title' => 'Limit Contacts',
    'message' => 'Import akan ditolak bila estimasi contact baru melebihi kapasitas plan tenant.',
])

<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('contacts.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload File</h3>
                </div>
                <div class="card-body">
                    <div class="col-12">
                        <label class="form-label">File Import <span class="text-danger">*</span></label>
                        <input type="file" name="import_file"
                            class="form-control @error('import_file') is-invalid @enderror"
                            accept=".csv,.txt,.xlsx" required>
                        <div class="form-hint">Format: <code>.csv</code>, <code>.txt</code>, <code>.xlsx</code>. Maks 10 MB.</div>
                        @error('import_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-file-import me-1"></i>Import Contacts
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Panduan Import</h3>
            </div>
            <div class="card-body">
                <div class="fw-semibold mb-2">Header kolom yang didukung:</div>
                <div class="small text-muted mb-3" style="line-height:1.8;">
                    <code>type</code>, <code>name</code>, <code>company_name</code>, <code>job_title</code>,
                    <code>email</code>, <code>phone</code>, <code>mobile</code>, <code>website</code>,
                    <code>vat</code>, <code>company_registry</code>, <code>industry</code>,
                    <code>street</code>, <code>street2</code>, <code>city</code>, <code>state</code>,
                    <code>zip</code>, <code>country</code>, <code>notes</code>, <code>is_active</code>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-circle-check text-green mt-1"></i>
                            <div>Kolom minimum wajib: <strong>name</strong></div>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-info-circle text-azure mt-1"></i>
                            <div>Isi <code>company_name</code> untuk kontak individual yang terkait perusahaan.</div>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-info-circle text-azure mt-1"></i>
                            <div>Nomor <code>mobile</code> diawali <code>08</code> otomatis dikonversi ke <code>628</code>.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
