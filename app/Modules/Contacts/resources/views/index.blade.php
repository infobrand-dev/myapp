@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Contacts</h2>
        <div class="text-muted small">Database perusahaan dan individu.</div>
    </div>
    <div class="btn-list">
        <a href="{{ route('contacts.import-template', 'csv') }}" class="btn btn-outline-secondary">Download Template CSV</a>
        <a href="{{ route('contacts.import-template', 'xlsx') }}" class="btn btn-outline-secondary">Download Template XLSX</a>
        <a href="{{ route('contacts.create') }}" class="btn btn-primary">Tambah Contact</a>
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
            @foreach(collect(session('import_skipped'))->take(8) as $rowError)
                <li>{{ $rowError }}</li>
            @endforeach
        </ul>
        @if(count(session('import_skipped')) > 8)
            <div class="small text-muted mt-2">Menampilkan 8 error pertama dari {{ count(session('import_skipped')) }} baris yang dilewati.</div>
        @endif
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-7">
                <div class="fw-semibold">Import Contacts</div>
                <div class="text-muted small">Upload file CSV atau XLSX dengan header template. Sistem akan mencoba mencocokkan header umum seperti <code>nama</code>, <code>company</code>, <code>mobile</code>, dan <code>email</code> tanpa mapping manual.</div>
            </div>
            <div class="col-lg-5">
                <form method="POST" action="{{ route('contacts.import') }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <div class="col-md-8">
                        <input type="file" name="import_file" class="form-control" accept=".csv,.txt,.xlsx" required>
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
                <div class="text-muted small mt-2">Kolom minimum yang wajib ada: <code>name</code>. Untuk individual yang terhubung ke perusahaan, isi <code>company_name</code>.</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                <tr>
                    <td>
                        <a href="{{ route('contacts.show', $contact) }}" class="text-decoration-none">
                            {{ $contact->name }}
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-{{ $contact->type === 'company' ? 'primary' : 'azure' }}-lt text-{{ $contact->type === 'company' ? 'primary' : 'azure' }}">
                            {{ $contact->type === 'company' ? 'Company' : 'Individual' }}
                        </span>
                    </td>
                    <td>{{ $contact->company?->name ?? '-' }}</td>
                    <td>{{ $contact->email ?? '-' }}</td>
                    <td>{{ $contact->phone ?? $contact->mobile ?? '-' }}</td>
                    <td class="text-end align-middle">
                        <div class="table-actions">
                            <a class="btn btn-icon btn-outline-secondary" href="{{ route('contacts.edit', $contact) }}" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3l-11 11l-4 1l1 -4z" />
                                </svg>
                            </a>
                            <form class="d-inline-block m-0" method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('Hapus contact ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-icon btn-outline-danger" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 7h16" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                        <path d="M9 7v-3h6v3" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">Belum ada contact.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $contacts->links() }}
    </div>
</div>
@endsection
