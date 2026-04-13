@extends('layouts.admin')

@section('title', 'Contacts')

@section('content')
@php
    $hooks = app(\App\Support\HookManager::class);
    $hasFilters = !empty(array_filter($filters ?? []));
    $typeFilter = $filters['type'] ?? '';
    $searchFilter = $filters['search'] ?? '';
    $avatarPalette = ['#206bc4', '#2fb344', '#f59f00', '#d63939', '#ae3ec9', '#0ca678', '#4299e1', '#e67700', '#f76707', '#1c7ed6'];
@endphp

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

@if(in_array(($contactLimitState['status'] ?? 'ok'), ['at_limit', 'over_limit'], true))
    @include('shared.plan-limit-alert', [
        'state' => $contactLimitState,
        'title' => 'Limit Contacts',
        'message' => ($contactLimitState['status'] ?? '') === 'over_limit'
            ? 'Jumlah contacts sudah melewati batas plan. Contact baru diblokir sampai kapasitas turun atau plan berubah.'
            : 'Kapasitas contacts sudah penuh. Contact baru tidak bisa ditambahkan.',
    ])
@endif

<div class="card">
    {{-- Filter Bar --}}
    <div class="card-header">
        <form method="GET" action="{{ route('contacts.index') }}" id="filter-form">
            <div class="row g-2 align-items-center">
                {{-- Search --}}
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Cari nama, email, atau nomor telepon…"
                            value="{{ $searchFilter }}" autocomplete="off">
                    </div>
                </div>

                {{-- Type Filter (radio as btn-group) --}}
                <div class="col-auto">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="type" id="type-all" value=""
                            autocomplete="off" {{ $typeFilter === '' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary" for="type-all">Semua</label>

                        <input type="radio" class="btn-check" name="type" id="type-company" value="company"
                            autocomplete="off" {{ $typeFilter === 'company' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary" for="type-company">
                            <i class="ti ti-building me-1"></i>Company
                        </label>

                        <input type="radio" class="btn-check" name="type" id="type-individual" value="individual"
                            autocomplete="off" {{ $typeFilter === 'individual' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary" for="type-individual">
                            <i class="ti ti-user me-1"></i>Individual
                        </label>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-search me-1"></i>Cari
                    </button>
                    @if($hasFilters)
                        <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary" title="Reset filter">
                            <i class="ti ti-filter-off"></i>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Active filter chips --}}
            @if($hasFilters)
                <div class="d-flex flex-wrap gap-2 align-items-center mt-3 pt-3 border-top">
                    <span class="text-muted small">Filter aktif:</span>
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

    {{-- Table --}}
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
                        <th>Terms</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                    @php
                        $avatarColor = $avatarPalette[abs(crc32($contact->name)) % count($avatarPalette)];
                        $parts = explode(' ', trim($contact->name));
                        $initials = strtoupper($parts[0][0] ?? '?');
                        if (count($parts) >= 2) {
                            $initials = strtoupper($parts[0][0] . $parts[count($parts) - 1][0]);
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
                                        class="fw-semibold text-body text-decoration-none d-block">
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
                        <td class="text-muted">
                            <div>{{ $contact->parentContact?->name ?? '—' }}</div>
                            @if(!empty($contact->tags))
                                <div class="mt-1">
                                    @foreach(array_slice($contact->tags, 0, 2) as $tag)
                                        <span class="badge bg-azure-lt text-azure me-1">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-muted">
                            <div>{{ $contact->payment_term_days !== null ? $contact->payment_term_days . ' hari' : '—' }}</div>
                            <div class="small">{{ $contact->credit_limit !== null ? app(\App\Support\MoneyFormatter::class)->format((float) $contact->credit_limit, app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency()) : '—' }}</div>
                        </td>
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
                        <td colspan="7" class="text-center py-5">
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
