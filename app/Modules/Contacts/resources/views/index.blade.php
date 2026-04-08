@extends('layouts.admin')

@section('title', 'Contacts')

@section('content')
@php($hooks = app(\App\Support\HookManager::class))
@php($hasFilters = !empty(array_filter($filters ?? [])))

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">CRM</div>
            <h2 class="page-title">Contacts</h2>
            <p class="text-muted mb-0">Daftar kontak pelanggan dan supplier.</p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap align-items-center">
            @if(($mergeCandidateCount ?? 0) > 0)
                <a href="{{ route('contacts.merge-candidates') }}" class="btn btn-outline-warning">
                    <i class="ti ti-git-merge me-1"></i>Merge
                    <span class="badge bg-warning text-white ms-1">{{ $mergeCandidateCount }}</span>
                </a>
            @endif
            <a href="{{ route('contacts.import-page') }}" class="btn btn-outline-secondary" title="Import Contacts">
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

<div class="card">
    {{-- Search + Filter toggle bar --}}
    <div class="card-header">
        <form method="GET" action="{{ route('contacts.index') }}" id="filter-form">
            <div class="d-flex gap-2 align-items-center">
                <div class="input-group" style="max-width:320px;">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, email, telepon…"
                        value="{{ $filters['search'] ?? '' }}" autocomplete="off">
                    @if(!empty($filters['search'] ?? ''))
                        <a href="{{ route('contacts.index', array_filter(array_merge($filters, ['search' => null]))) }}"
                            class="btn btn-outline-secondary" title="Clear search">
                            <i class="ti ti-x"></i>
                        </a>
                    @endif
                </div>

                {{-- Filter toggle --}}
                <button type="button" class="btn {{ $hasFilters ? 'btn-primary' : 'btn-outline-secondary' }}"
                    data-bs-toggle="collapse" data-bs-target="#filter-panel" aria-expanded="{{ $hasFilters ? 'true' : 'false' }}">
                    <i class="ti ti-adjustments-horizontal me-1"></i>Filter
                    @if($hasFilters)
                        <span class="badge bg-white text-primary ms-1">
                            {{ count(array_filter($filters ?? [])) }}
                        </span>
                    @endif
                </button>

                @if($hasFilters)
                    <a href="{{ route('contacts.index') }}" class="btn btn-ghost-secondary btn-sm" title="Reset semua filter">
                        <i class="ti ti-filter-off me-1"></i>Reset
                    </a>
                @endif

                <div class="ms-auto text-muted" style="font-size:.8rem;">
                    {{ $contacts->total() }} kontak
                </div>
            </div>

            {{-- Collapsible filter panel --}}
            <div class="collapse mt-3 {{ $hasFilters ? 'show' : '' }}" id="filter-panel">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select">
                            <option value="">Semua tipe</option>
                            <option value="company" @selected(($filters['type'] ?? '') === 'company')>Company</option>
                            <option value="individual" @selected(($filters['type'] ?? '') === 'individual')>Individual</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-search me-1"></i>Terapkan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Applied filter chips --}}
    @if($hasFilters)
        <div class="card-body py-2 border-bottom" style="background:#f8f9fa;">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted" style="font-size:.78rem;">Filter aktif:</span>
                @if(!empty($filters['search'] ?? ''))
                    <span class="badge bg-blue-lt text-blue d-flex align-items-center gap-1">
                        <i class="ti ti-search"></i> "{{ $filters['search'] }}"
                        <a href="{{ route('contacts.index', array_filter(array_merge($filters, ['search' => null]))) }}"
                            class="text-blue ms-1" style="line-height:1;">×</a>
                    </span>
                @endif
                @if(!empty($filters['type'] ?? ''))
                    <span class="badge bg-blue-lt text-blue d-flex align-items-center gap-1">
                        <i class="ti ti-tag"></i> {{ ucfirst($filters['type']) }}
                        <a href="{{ route('contacts.index', array_filter(array_merge($filters, ['type' => null]))) }}"
                            class="text-blue ms-1" style="line-height:1;">×</a>
                    </span>
                @endif
            </div>
        </div>
    @endif

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
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
                            <a href="{{ route('contacts.show', $contact) }}" class="fw-semibold text-decoration-none">
                                {{ $contact->name }}
                            </a>
                            @php($scope = \App\Modules\Contacts\Support\ContactScope::detectLevel($contact))
                            @if($scope !== 'tenant')
                                <div class="text-muted small">{{ ucfirst($scope) }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $contact->type === 'company' ? 'bg-blue-lt text-blue' : 'bg-azure-lt text-azure' }}">
                                {{ $contact->type === 'company' ? 'Company' : 'Individual' }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $contact->parentContact?->name ?? '-' }}</td>
                        <td class="text-muted">{{ $contact->email ?? '-' }}</td>
                        <td class="text-muted">{{ $contact->phone ?? $contact->mobile ?? '-' }}</td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                @foreach($hooks->render('contacts.index.row_actions', ['contact' => $contact]) as $hookedAction)
                                    {!! $hookedAction !!}
                                @endforeach
                                <a class="btn btn-icon btn-sm btn-outline-secondary" href="{{ route('contacts.show', $contact) }}" title="Lihat Detail">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a class="btn btn-icon btn-sm btn-outline-primary" href="{{ route('contacts.edit', $contact) }}" title="Edit">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form class="d-inline-block m-0" method="POST" action="{{ route('contacts.destroy', $contact) }}">
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
                                <div class="text-muted mb-2">Tidak ada kontak yang cocok dengan filter.</div>
                                <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary">Reset Filter</a>
                            @else
                                <div class="text-muted mb-2">Belum ada kontak.</div>
                                @can('contacts.create')
                                    <a href="{{ route('contacts.create') }}" class="btn btn-sm btn-primary">Tambah Contact</a>
                                @endcan
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $contacts->links() }}
    </div>
</div>

@foreach($hooks->render('contacts.index.after_content', ['contacts' => $contacts]) as $hookedContent)
    {!! $hookedContent !!}
@endforeach
@endsection
