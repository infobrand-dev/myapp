@extends('layouts.admin')

@section('title', 'Merge Contacts')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">CRM · Contacts</div>
            <h2 class="page-title">Merge Contacts</h2>
            <p class="text-muted mb-0">Kontak duplikat terdeteksi berdasarkan nomor telepon atau email yang sama.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible mb-3">
        <i class="ti ti-alert-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(count($groups) > 0)
    <div class="alert alert-warning mb-3">
        <i class="ti ti-git-merge me-2"></i>
        Ditemukan <strong>{{ count($groups) }}</strong> grup kontak duplikat.
        Pilih satu kontak sebagai <em>utama</em>, centang sisanya untuk digabungkan.
        Data kontak utama dipertahankan — field kosong akan diisi dari kontak lain.
    </div>
@endif

@forelse($groups as $group)
    @php
        $contacts = $group['contacts'];
        $defaultPrimaryId = old('primary_id') !== null ? (int) old('primary_id') : (int) $group['default_primary_id'];
        $oldDuplicateIds = collect(old('duplicate_ids', $contacts->pluck('id')->reject(fn ($id) => (int) $id === $defaultPrimaryId)->all()))
            ->map(fn ($id) => (int) $id)
            ->all();
    @endphp
    <div class="card mb-3">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <span class="badge bg-orange-lt text-orange me-2">{{ $group['match_label'] }}</span>
                    {{ $group['match_value'] }}
                </h3>
                <p class="text-muted mb-0">{{ $contacts->count() }} kontak cocok. Pilih satu sebagai data utama.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('contacts.merge') }}">
            @csrf
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th class="w-1 text-center ps-3">Utama</th>
                                <th class="w-1 text-center">Gabung</th>
                                <th>Nama</th>
                                <th>Tipe</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Mobile</th>
                                <th>Company</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contacts as $contact)
                                @php
                                    $contactId = (int) $contact->id;
                                @endphp
                                <tr>
                                    <td class="text-center ps-3">
                                        <input class="form-check-input" type="radio"
                                            name="primary_id" value="{{ $contactId }}"
                                            {{ $defaultPrimaryId === $contactId ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox"
                                            name="duplicate_ids[]" value="{{ $contactId }}"
                                            {{ in_array($contactId, $oldDuplicateIds, true) ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <a href="{{ route('contacts.show', $contact) }}" target="_blank" class="fw-semibold text-decoration-none">
                                            {{ $contact->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge {{ $contact->type === 'company' ? 'bg-blue-lt text-blue' : 'bg-azure-lt text-azure' }}">
                                            {{ ucfirst($contact->type) }}
                                        </span>
                                    </td>
                                    <td class="text-muted">{{ $contact->email ?? '-' }}</td>
                                    <td class="text-muted">{{ $contact->phone ?? '-' }}</td>
                                    <td class="text-muted">{{ $contact->mobile ?? '-' }}</td>
                                    <td class="text-muted">{{ $contact->parentContact?->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="ti ti-info-circle me-1"></i>
                    Kontak yang dicentang "Gabung" akan dihapus setelah merge.
                </div>
                <button type="submit" class="btn btn-warning"
                    data-confirm="Gabungkan kontak yang dipilih? Kontak duplikat akan dihapus.">
                    <i class="ti ti-git-merge me-1"></i>Merge Grup Ini
                </button>
            </div>
        </form>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="ti ti-circle-check text-green d-block mb-2" style="font-size:2.5rem;"></i>
            <div class="fw-semibold mb-1">Tidak ada duplikat!</div>
            <div class="text-muted mb-3">Semua kontak terlihat unik berdasarkan telepon dan email.</div>
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali ke Contacts
            </a>
        </div>
    </div>
@endforelse
@endsection
