@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $contact->name }}</h2>
        <div class="text-muted small">
            {{ $contact->type === 'company' ? 'Company' : 'Individual' }}
            @if($contact->company)
                · Bekerja di {{ $contact->company->name }}
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-outline-secondary">Edit</a>
        <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Utama</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $contact->email ?? '-' }}</dd>
                    <dt class="col-sm-4">Telepon</dt>
                    <dd class="col-sm-8">{{ $contact->phone ?? '-' }}</dd>
                    <dt class="col-sm-4">Mobile</dt>
                    <dd class="col-sm-8">{{ $contact->mobile ?? '-' }}</dd>
                    <dt class="col-sm-4">Website</dt>
                    <dd class="col-sm-8">{{ $contact->website ?? '-' }}</dd>
                    <dt class="col-sm-4">Jabatan</dt>
                    <dd class="col-sm-8">{{ $contact->job_title ?? '-' }}</dd>
                    <dt class="col-sm-4">Industry</dt>
                    <dd class="col-sm-8">{{ $contact->industry ?? '-' }}</dd>
                    <dt class="col-sm-4">VAT/NPWP</dt>
                    <dd class="col-sm-8">{{ $contact->vat ?? '-' }}</dd>
                    <dt class="col-sm-4">Company Registry</dt>
                    <dd class="col-sm-8">{{ $contact->company_registry ?? '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Alamat</h3>
            </div>
            <div class="card-body">
                <div>{{ $contact->street ?? '-' }}</div>
                @if($contact->street2)
                    <div>{{ $contact->street2 }}</div>
                @endif
                <div>{{ $contact->city ?? '-' }} {{ $contact->zip ?? '' }}</div>
                <div>{{ $contact->state ?? '-' }}</div>
                <div>{{ $contact->country ?? '-' }}</div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Catatan</h3>
            </div>
            <div class="card-body">
                {{ $contact->notes ?? '-' }}
            </div>
        </div>
    </div>
    @if($contact->type === 'company')
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Individu di Company</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Email</th>
                                <th>Telepon</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contact->employees as $employee)
                                <tr>
                                    <td><a href="{{ route('contacts.show', $employee) }}">{{ $employee->name }}</a></td>
                                    <td>{{ $employee->job_title ?? '-' }}</td>
                                    <td>{{ $employee->email ?? '-' }}</td>
                                    <td>{{ $employee->phone ?? $employee->mobile ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Belum ada individu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
