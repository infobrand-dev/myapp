@extends('layouts.admin')

@section('content')
@php
    $moduleStats = [
        'active' => collect($modules)->where('active', true)->where('installed', true)->count(),
        'installed' => collect($modules)->where('installed', true)->where('active', false)->count(),
        'not_installed' => collect($modules)->where('installed', false)->count(),
        'total' => count($modules),
    ];
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
    <div>
        <h2 class="mb-1">Modules</h2>
        <div class="text-muted small">Kelola installasi dan aktivasi module. Dependency dicek sebelum activate atau deactivate agar core app tetap aman.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <span class="badge bg-success-lt text-success px-3 py-2">Active: {{ $moduleStats['active'] }}</span>
        <span class="badge bg-warning-lt text-warning px-3 py-2">Installed: {{ $moduleStats['installed'] }}</span>
        <span class="badge bg-secondary-lt text-secondary px-3 py-2">Not Installed: {{ $moduleStats['not_installed'] }}</span>
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
                <div class="text-secondary text-uppercase small fw-bold">Pending Install</div>
                <div class="fs-1 fw-bold mt-2 text-secondary">{{ $moduleStats['not_installed'] }}</div>
                <div class="text-muted small mt-2">Masih berupa manifest dan belum dipasang.</div>
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
                                @if(!$module['installed'])
                                    @can('modules.install')
                                        <form
                                            method="POST"
                                            action="{{ route('modules.install', $module['slug']) }}"
                                            onsubmit='if (!confirm("Install module " + @json($module["name"]) + "? Migration dan seeder module akan dijalankan bila tersedia.")) { return false; } const button = this.querySelector("button[type=submit]"); if (button) { button.disabled = true; button.textContent = "Installing..."; }'
                                        >
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">Install</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">No permission</span>
                                    @endcan
                                @elseif(!$module['active'])
                                    @can('modules.activate')
                                        <form
                                            method="POST"
                                            action="{{ route('modules.activate', $module['slug']) }}"
                                            onsubmit='if (!confirm("Activate module " + @json($module["name"]) + "? Pastikan semua dependency sudah aktif.")) { return false; } const button = this.querySelector("button[type=submit]"); if (button) { button.disabled = true; button.textContent = "Activating..."; }'
                                        >
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Activate</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">No permission</span>
                                    @endcan
                                @else
                                    @can('modules.deactivate')
                                        <form
                                            method="POST"
                                            action="{{ route('modules.deactivate', $module['slug']) }}"
                                            onsubmit='if (!confirm("Deactivate module " + @json($module["name"]) + "? Module dependent yang masih aktif akan memblokir proses ini.")) { return false; } const button = this.querySelector("button[type=submit]"); if (button) { button.disabled = true; button.textContent = "Deactivating..."; }'
                                        >
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">No permission</span>
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
                        <td colspan="6" class="text-center text-muted py-5">
                            Tidak ada module yang cocok dengan filter saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
