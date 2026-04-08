@extends('layouts.admin')

@section('title', 'Contacts')

@section('content')
@php($hooks = app(\App\Support\HookManager::class))
@php($hasFilters = !empty(array_filter($filters ?? [])))
@php($typeFilter = $filters['type'] ?? '')
@php($searchFilter = $filters['search'] ?? '')
@php($avatarPalette = ['#206bc4','#2fb344','#f59f00','#d63939','#ae3ec9','#0ca678','#4299e1','#e67700','#f76707','#1c7ed6'])

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">CRM</div>
            <h2 class="page-title">Contacts</h2>
            <p class="text-muted mb-0">Kelola kontak pelanggan, supplier, dan mitra bisnis.</p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap align-items-center">
            @if(($mergeCandidateCount ?? 0) > 0)
                <a href="{{ route('contacts.merge-candidates') }}" class="btn btn-outline-warning">
                    <i class="ti ti-git-merge me-1"></i>Merge Duplikat
                    <span class="badge bg-warning text-white ms-1">{{ $mergeCandidateCount }}</span>
                </a>
            @endif
            <a href="{{ route('contacts.import-page') }}" class="btn btn-outline-secondary">
                <i class="ti ti-file-import me-1"></i>Import
            </a>
            @can('contacts.create')
                <a href="{{ route('contacts.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Tambah Contact
                </a>
            @endcan
        </div>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-azure alert-dismissible mb-3">
        <i class="ti ti-info-circle me-2"></i>{{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@include('shared.plan-limit-alert', [
    'state' => $contactLimitState,
    'title' => 'Limit Contacts',
    'message' => match (($contactLimitState['status'] ?? 'ok')) {
        'near_limit' => 'Kapasitas contacts tenant ini sudah mendekati batas plan.',
        'at_limit' => 'Kapasitas contacts tenant ini sudah penuh. Contact baru tidak bisa ditambahkan.',
        'over_limit' => 'Jumlah contacts tenant ini sudah melewati batas plan. Contact baru tetap diblokir sampai kapasitas turun atau plan berubah.',
        default => 'Kapasitas contacts mengikuti plan tenant yang sedang aktif.',
    },
])

{{-- ── Toolbar: Search + Quick Filter ── --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('contacts.index') }}" id="filter-form">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text text-muted"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Cari nama, email, atau nomor telepon…"
                            value="{{ $searchFilter }}" autocomplete="off">
                        @if($searchFilter)
                            <a href="{{ route('contacts.index', array_filter(array_merge($filters, ['search' => null]))) }}"
                                class="btn btn-outline-secondary" title="Hapus pencarian">
                                <i class="ti ti-x"></i>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="col-auto">
                    <div class="btn-group" role="group" aria-label="Filter tipe">
                        <a href="{{ route('contacts.index', array_filter(array_merge($filters, ['type' => null]))) }}"
                            class="btn {{ !$typeFilter ? 'btn-secondary' : 'btn-outline-secondary' }}">
                            Semua
                        </a>
                        <a href="{{ route('contacts.index', array_merge($filters, ['type' => 'company'])) }}"
                            class="btn {{ $typeFilter === 'company' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                            <i class="ti ti-building me-1"></i>Company
                        </a>
                        <a href="{{ route('contacts.index', array_merge($filters, ['type' => 'individual'])) }}"
                            class="btn {{ $typeFilter === 'individual' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                            <i class="ti ti-user me-1"></i>Individual
                        </a>
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-search me-1"></i>Cari
                    </button>
                    @if($hasFilters)
                        <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary" title="Reset semua filter">
                            <i class="ti ti-filter-off me-1"></i>Reset
                        </a>
                    @endif
                </div>
            </div>

            {{-- Active filter chips --}}
            @if($hasFilters)
                <div class="d-flex flex-wrap gap-2 align-items-center mt-2 pt-2 border-top">
                    <span class="text-muted" style="font-size:.78rem;">Filter aktif:</span>
                    @if($searchFilter)
                        <span class="badge bg-blue-lt text-blue d-flex align-items-center gap-1">
                            <i class="ti ti-search" style="font-size:.7rem;"></i>
                            "{{ $searchFilter }}"
                            <a href="{{ route('contacts.index', array_filter(array_merge($filters, ['search' => null]))) }}"
                                class="text-blue ms-1" style="line-height:1; text-decoration:none;">×</a>
                        </span>
                    @endif
                    @if($typeFilter)
                        <span class="badge bg-blue-lt text-blue d-flex align-items-center gap-1">
                            <i class="ti ti-tag" style="font-size:.7rem;"></i>
                            {{ ucfirst($typeFilter) }}
                            <a href="{{ route('contacts.index', array_filter(array_merge($filters, ['type' => null]))) }}"
                                class="text-blue ms-1" style="line-height:1; text-decoration:none;">×</a>
                        </span>
                    @endif
                </div>
            @endif
        </form>
    </div>
</div>

{{-- ── Contacts Table ── --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Company</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                    @php
                        $avatarColor = $avatarPalette[abs(crc32($contact->name)) % count($avatarPalette)];
                        $initials = strtoupper(substr(trim($contact->name), 0, 1));
                        $parts = explode(' ', trim($contact->name));
                        if (count($parts) >= 2) {
                            $initials = strtoupper($parts[0][0] . $parts[count($parts)-1][0]);
                        }
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar avatar-sm flex-shrink-0"
                                    style="background:{{ $avatarColor }}; color:#fff; font-size:.65rem; font-weight:600;">
                                    {{ $initials }}
                                </span>
                                <div>
                                    <a href="{{ route('contacts.show', $contact) }}"
                                        class="fw-semibold text-decoration-none text-body d-block">
                                        {{ $contact->name }}
                                    </a>
                                    @if($contact->job_title)
                                        <div class="text-muted small">{{ $contact->job_title }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($contact->type === 'company')
                                <span class="badge bg-blue-lt text-blue">
                                    <i class="ti ti-building me-1"></i>Company
                                </span>
                            @else
                                <span class="badge bg-azure-lt text-azure">
                                    <i class="ti ti-user me-1"></i>Individual
                                </span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $contact->email ?? '—' }}</td>
                        <td class="text-muted">{{ $contact->phone ?? $contact->mobile ?? '—' }}</td>
                        <td class="text-muted">{{ $contact->parentContact?->name ?? '—' }}</td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                @foreach($hooks->render('contacts.index.row_actions', ['contact' => $contact]) as $hookedAction)
                                    {!! $hookedAction !!}
                                @endforeach
                                <a class="btn btn-icon btn-sm btn-outline-secondary"
                                    href="{{ route('contacts.show', $contact) }}" title="Lihat Detail">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a class="btn btn-icon btn-sm btn-outline-primary"
                                    href="{{ route('contacts.edit', $contact) }}" title="Edit">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form class="d-inline-block m-0" method="POST"
                                    action="{{ route('contacts.destroy', $contact) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-icon btn-sm btn-outline-danger" title="Hapus"
                                        data-confirm="Hapus kontak {{ $contact->name }}?">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="ti ti-address-book text-muted d-block mb-2" style="font-size:2rem;"></i>
                            @if($hasFilters)
                                <div class="text-muted mb-2">Tidak ada kontak yang cocok dengan filter aktif.</div>
                                <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="ti ti-filter-off me-1"></i>Reset Filter
                                </a>
                            @else
                                <div class="text-muted mb-2">Belum ada kontak.</div>
                                @can('contacts.create')
                                    <a href="{{ route('contacts.create') }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-plus me-1"></i>Tambah Contact
                                    </a>
                                @endcan
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex align-items-center justify-content-between">
        <div class="text-muted small">
            {{ number_format($contacts->total()) }} kontak ditemukan
        </div>
        <div>{{ $contacts->links() }}</div>
    </div>
</div>

@foreach($hooks->render('contacts.index.after_content', ['contacts' => $contacts]) as $hookedContent)
    {!! $hookedContent !!}
@endforeach
@endsection
