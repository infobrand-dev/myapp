@extends('layouts.admin')

@section('content')
@php
    $moduleStats = [
        'active' => collect($modules)->where('active', true)->where('installed', true)->count(),
        'installed' => collect($modules)->where('installed', true)->where('active', false)->count(),
        'not_installed' => collect($modules)->where('installed', false)->count(),
        'pending_db_update' => collect($modules)->where('has_pending_db_update', true)->count(),
        'filesystem_issues' => collect($modules)->where('has_filesystem_issues', true)->count(),
        'total' => count($modules),
    ];
@endphp

<div class="page-header d-flex align-items-start align-items-md-center justify-content-between flex-column flex-md-row gap-3">
    <div>
        <div class="page-pretitle">Administrasi</div>
        <h2 class="page-title">Modules</h2>
        <div class="text-muted small mt-1">Kelola instalasi dan aktivasi modul. Dependency dicek otomatis sebelum aktivasi atau deaktivasi.</div>
    </div>
    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
        <span class="badge bg-success-lt text-success px-3 py-2">
            <i class="ti ti-circle-check me-1"></i>Aktif: {{ $moduleStats['active'] }}
        </span>
        <span class="badge bg-warning-lt text-warning px-3 py-2">
            <i class="ti ti-package me-1"></i>Terpasang: {{ $moduleStats['installed'] }}
        </span>
        <span class="badge bg-orange-lt text-orange px-3 py-2">
            <i class="ti ti-database-exclamation me-1"></i>Pending DB: {{ $moduleStats['pending_db_update'] }}
        </span>
        <span class="badge bg-red-lt text-red px-3 py-2">
            <i class="ti ti-folder-x me-1"></i>Path Issue: {{ $moduleStats['filesystem_issues'] }}
        </span>
        <span class="badge bg-secondary-lt text-secondary px-3 py-2">
            <i class="ti ti-package-off me-1"></i>Belum pasang: {{ $moduleStats['not_installed'] }}
        </span>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Visible Modules</div>
                <div class="fs-1 fw-bold mt-2">{{ $moduleStats['total'] }}</div>
                <div class="text-muted small mt-2">Hasil setelah filter diterapkan.</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Active</div>
                <div class="fs-1 fw-bold mt-2 text-success">{{ $moduleStats['active'] }}</div>
                <div class="text-muted small mt-2">Module aktif dan route/provider sudah berjalan.</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Installed Only</div>
                <div class="fs-1 fw-bold mt-2 text-warning">{{ $moduleStats['installed'] }}</div>
                <div class="text-muted small mt-2">Sudah di-install tetapi belum di-activate.</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Path Issue</div>
                <div class="fs-1 fw-bold mt-2 text-red">{{ $moduleStats['filesystem_issues'] }}</div>
                <div class="text-muted small mt-2">Mismatch path/casing yang berisiko saat deploy ke Linux.</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Name, slug, description">
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ \Illuminate\Support\Str::headline($category) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All status</option>
                    <option value="active" @selected($filters['status'] === 'active')>Active</option>
                    <option value="installed" @selected($filters['status'] === 'installed')>Installed</option>
                    <option value="pending-db-update" @selected($filters['status'] === 'pending-db-update')>Pending DB Update</option>
                    <option value="filesystem-issue" @selected($filters['status'] === 'filesystem-issue')>Filesystem Issue</option>
                    <option value="not-installed" @selected($filters['status'] === 'not-installed')>Not Installed</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill">Filter</button>
                <a href="{{ route('modules.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-3">
        <div>
            <h3 class="card-title mb-0">Module Registry</h3>
            <div class="text-muted small mt-1">Aktivasi mengikuti deklarasi dependency pada `module.json` dan provider module aktif akan diregister otomatis.</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th style="min-width: 21rem;">Module</th>
                    <th>Category</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>DB Status</th>
                    <th style="min-width: 14rem;">Dependencies</th>
                    <th class="text-end" style="min-width: 12rem;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($modules as $module)
                    @php
                        $statusLabel = !$module['installed']
                            ? ['label' => 'Not Installed', 'class' => 'secondary']
                            : ($module['active']
                                ? ['label' => 'Active', 'class' => 'success']
                                : ['label' => 'Installed', 'class' => 'warning']);
                        $dbStatus = !$module['installed']
                            ? ['label' => '-', 'class' => 'secondary']
                            : ($module['has_filesystem_issues']
                                ? ['label' => 'Filesystem Issue', 'class' => 'red']
                            : ($module['has_pending_db_update']
                                ? ['label' => 'Pending DB Update', 'class' => 'orange']
                                : ['label' => 'Up to Date', 'class' => 'success']));
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-start gap-3">
                                <div class="mt-1">
                                    @include('shared.module-icon', ['module' => $module, 'size' => 26])
                                </div>
                                <div class="d-flex flex-column gap-1">
                                    <div class="fw-semibold">{{ $module['name'] }}</div>
                                    <div class="text-muted small">{{ $module['slug'] }}</div>
                                    @if($module['description'])
                                        <div class="text-muted small">{{ $module['description'] }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-blue-lt text-blue">{{ \Illuminate\Support\Str::headline($module['category']) }}</span>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $module['version'] ?: '-' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $statusLabel['class'] }}-lt text-{{ $statusLabel['class'] }}">{{ $statusLabel['label'] }}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <span class="badge bg-{{ $dbStatus['class'] }}-lt text-{{ $dbStatus['class'] }}">{{ $dbStatus['label'] }}</span>
                                @if($module['has_filesystem_issues'])
                                    <div class="text-danger small">
                                        {{ implode('; ', $module['filesystem_issues']) }}
                                    </div>
                                @elseif($module['has_pending_db_update'])
                                    <div class="text-muted small">
                                        {{ count($module['pending_migrations']) }} migration pending
                                    </div>
                                    <div class="text-muted small">
                                        {{ implode(', ', array_slice($module['pending_migrations'], 0, 2)) }}{{ count($module['pending_migrations']) > 2 ? ' ...' : '' }}
                                    </div>
                                @elseif($module['last_db_update_status'] === 'failed')
                                    <div class="text-danger small">{{ $module['last_db_update_error'] }}</div>
                                @elseif($module['last_db_update_at'])
                                    <div class="text-muted small">Last run: {{ \Illuminate\Support\Carbon::parse($module['last_db_update_at'])->format('d M Y H:i') }}</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if(empty($module['requires']))
                                <span class="text-muted small">No dependency</span>
                            @else
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($module['requires'] as $req)
                                        <span class="badge bg-azure-lt text-azure">{{ $req }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-column align-items-end gap-2">
                                @if($module['installed'] && $module['has_pending_db_update'] && !$module['has_filesystem_issues'])
                                    @can('modules.activate')
                                        <form method="POST" action="{{ route('modules.db-update', $module['slug']) }}" class="module-action-form">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-orange"
                                                data-confirm="Jalankan pending DB update untuk modul {{ $module['name'] }}? Hanya migration modul ini yang akan dijalankan."
                                                data-loading="Running DB Update...">
                                                Run DB Update
                                            </button>
                                        </form>
                                    @endcan
                                @elseif($module['installed'] && $module['has_filesystem_issues'])
                                    <span class="text-danger small text-end">Perbaiki path/casing dulu sebelum DB update.</span>
                                @endif

                                @if(!$module['installed'])
                                    @can('modules.install')
                                        <form method="POST" action="{{ route('modules.install', $module['slug']) }}" class="module-action-form">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                data-confirm="Install modul {{ $module['name'] }}? Migration dan seeder akan dijalankan bila tersedia."
                                                data-loading="Installing...">
                                                Install
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Tidak ada akses</span>
                                    @endcan
                                @elseif(!$module['active'])
                                    @can('modules.activate')
                                        <form method="POST" action="{{ route('modules.activate', $module['slug']) }}" class="module-action-form">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success"
                                                data-confirm="Aktifkan modul {{ $module['name'] }}? Pastikan semua dependency sudah aktif."
                                                data-loading="Activating...">
                                                Activate
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Tidak ada akses</span>
                                    @endcan
                                @else
                                    @can('modules.deactivate')
                                        <form method="POST" action="{{ route('modules.deactivate', $module['slug']) }}" class="module-action-form">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                data-confirm="Nonaktifkan modul {{ $module['name'] }}? Modul dependent yang masih aktif akan memblokir proses ini."
                                                data-loading="Deactivating...">
                                                Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Tidak ada akses</span>
                                    @endcan
                                @endif

                                @if(!empty($module['dependents']))
                                    <div class="text-muted small text-end">
                                        Used by: {{ implode(', ', $module['dependents']) }}
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            Tidak ada module yang cocok dengan filter saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Setelah confirm modal OK, disable button dan tampilkan loading text
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-loading]');
        if (!btn || !btn.closest('.module-action-form')) return;
        // Tunggu confirm selesai lalu set loading state
        const originalConfirm = btn.getAttribute('data-confirm');
        if (!originalConfirm) return;
        const observer = new MutationObserver(() => {
            if (!btn.hasAttribute('data-confirm')) {
                btn.disabled = true;
                btn.textContent = btn.getAttribute('data-loading') || 'Processing...';
                observer.disconnect();
            }
        });
        observer.observe(btn, { attributes: true, attributeFilter: ['data-confirm'] });
    });
</script>
@endpush
