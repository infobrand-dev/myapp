@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Merge Contacts</h2>
        <div class="text-muted small">Deteksi kandidat duplikat berdasarkan nomor telepon/mobile atau email yang sama.</div>
    </div>
    <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
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
                <h3 class="card-title mb-1">{{ $group['match_label'] }} cocok: {{ $group['match_value'] }}</h3>
                <div class="text-muted small">{{ $contacts->count() }} contact terdeteksi dalam grup ini. Pilih satu contact utama lalu merge sisanya.</div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('contacts.merge') }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th class="w-1">Utama</th>
                                <th class="w-1">Merge</th>
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
                                @php($contactId = (int) $contact->id)
                                <tr>
                                    <td>
                                        <label class="form-check m-0">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="primary_id"
                                                value="{{ $contactId }}"
                                                {{ $defaultPrimaryId === $contactId ? 'checked' : '' }}
                                            >
                                        </label>
                                    </td>
                                    <td>
                                        <label class="form-check m-0">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="duplicate_ids[]"
                                                value="{{ $contactId }}"
                                                {{ in_array($contactId, $oldDuplicateIds, true) ? 'checked' : '' }}
                                            >
                                        </label>
                                    </td>
                                    <td>
                                        <a href="{{ route('contacts.show', $contact) }}" class="text-decoration-none">{{ $contact->name }}</a>
                                    </td>
                                    <td>{{ ucfirst($contact->type) }}</td>
                                    <td>{{ $contact->email ?? '-' }}</td>
                                    <td>{{ $contact->phone ?? '-' }}</td>
                                    <td>{{ $contact->mobile ?? '-' }}</td>
                                    <td>{{ $contact->parentContact?->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Nilai utama dipertahankan. Field kosong akan diisi dari contact lain, catatan digabung, dan relasi contact dipindahkan ke record utama.
                    </div>
                    <button type="submit" class="btn btn-warning" data-confirm="Gabungkan contact yang dipilih ke contact utama?">Merge Grup Ini</button>
                </div>
            </form>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-muted">
            Tidak ada kandidat merge berdasarkan nomor atau email yang sama.
        </div>
    </div>
@endforelse
@endsection
