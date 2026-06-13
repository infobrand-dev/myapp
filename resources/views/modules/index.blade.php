@extends('layouts.tenant')

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
    $canManageModules = (auth()->user()?->hasRole('Super-admin') ?? false)
        || (auth()->user()?->can('modules.activate') ?? false)
        || (auth()->user()?->can('modules.install') ?? false)
        || (auth()->user()?->can('modules.deactivate') ?? false);
    $canBulkInstall = (auth()->user()?->can('modules.install') ?? false);
    $canBulkActivate = (auth()->user()?->can('modules.activate') ?? false);
    $canBulkDeactivate = (auth()->user()?->can('modules.deactivate') ?? false);
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Administrasi</div>
            <h2 class="page-title">Modules</h2>
            <p class="text-muted mb-0">Kelola instalasi dan aktivasi modul. Dependency dicek otomatis sebelum aktivasi atau deaktivasi.</p>
        </div>
        <div class="col-auto d-flex flex-wrap gap-2">
            <span class="badge bg-green-lt text-green px-3 py-2">
                <i class="ti ti-circle-check me-1"></i>Aktif: {{ $moduleStats['active'] }}
            </span>
            <span class="badge bg-orange-lt text-orange px-3 py-2">
                <i class="ti ti-package me-1"></i>Terpasang: {{ $moduleStats['installed'] }}
            </span>
            <span class="badge bg-yellow-lt text-yellow px-3 py-2">
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
        @if($canManageModules)
            <form id="modules-bulk-form" class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                @csrf
                <select name="action" id="modules-bulk-action" class="form-select form-select-sm" style="min-width: 14rem;">
                    <option value="">Bulk action</option>
                    @if($canBulkInstall)
                        <option value="install">Install selected</option>
                    @endif
                    @if($canBulkActivate)
                        <option value="activate">Install + activate selected</option>
                        <option value="db-update">Run DB update selected</option>
                    @endif
                    @if($canBulkDeactivate)
                        <option value="deactivate">Deactivate selected</option>
                    @endif
                </select>
                <button type="button"
                    id="modules-bulk-run"
                    class="btn btn-sm btn-primary"
                    data-loading="Processing...">
                    Run Bulk Action
                </button>
                <div class="w-100 text-muted small text-end">
                    Install/activate otomatis mengikutkan dependency. Deactivate hanya aman bila dependent ikut dipilih.
                </div>
            </form>
        @endif
    </div>
    @if($canManageModules)
        <div class="card-body border-bottom" id="modules-bulk-panel" hidden>
            <div class="row g-3 align-items-start">
                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 h-100 bg-body-tertiary">
                        <div class="text-secondary text-uppercase small fw-bold">Bulk Progress</div>
                        <div class="fs-3 fw-bold mt-2" id="modules-bulk-progress-text">0 / 0</div>
                        <div class="progress progress-sm mt-2">
                            <div id="modules-bulk-progress-bar" class="progress-bar progress-bar-striped" style="width: 0%"></div>
                        </div>
                        <div class="small text-muted mt-2" id="modules-bulk-summary">Belum ada proses berjalan.</div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-sm btn-outline-danger" id="modules-bulk-stop" hidden>Stop After Current</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="modules-bulk-refresh" hidden>Refresh Page</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-secondary text-uppercase small fw-bold">Current Item</div>
                        <div class="fw-semibold mt-2" id="modules-bulk-current">Menunggu mulai.</div>
                        <div class="small text-muted mt-2" id="modules-bulk-current-detail">Pilih action dan module, lalu jalankan bulk action.</div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-secondary text-uppercase small fw-bold">Queue</div>
                        <div class="small text-muted mt-2" id="modules-bulk-queue-summary">Belum ada antrean.</div>
                        <div class="mt-2" style="max-height: 14rem; overflow: auto;">
                            <ul class="list-group list-group-flush" id="modules-bulk-queue"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th class="w-1">
                        @if($canManageModules)
                            <input type="checkbox" class="form-check-input" id="modules-select-all" aria-label="Select all modules">
                        @endif
                    </th>
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
                    <tr data-module-row data-module-slug="{{ $module['slug'] }}">
                        <td>
                            @if($canManageModules)
                                <input
                                    type="checkbox"
                                    class="form-check-input module-bulk-checkbox"
                                    name="slugs[]"
                                    value="{{ $module['slug'] }}"
                                    form="modules-bulk-form"
                                    aria-label="Select module {{ $module['name'] }}"
                                >
                            @endif
                        </td>
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
                            <span
                                class="badge bg-{{ $statusLabel['class'] }}-lt text-{{ $statusLabel['class'] }}"
                                data-module-status
                            >{{ $statusLabel['label'] }}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <span
                                    class="badge bg-{{ $dbStatus['class'] }}-lt text-{{ $dbStatus['class'] }}"
                                    data-module-db-status
                                >{{ $dbStatus['label'] }}</span>
                                @if($module['has_filesystem_issues'])
                                    <div class="text-danger small">
                                        {{ implode('; ', $module['filesystem_issues']) }}
                                    </div>
                                @elseif($module['has_pending_db_update'])
                                    <div class="text-muted small">
                                        {{ count($module['pending_migrations']) }} migration pending
                                    </div>
                                    <div class="d-flex flex-column gap-1 mt-1">
                                        @foreach($module['pending_migration_files'] as $migration)
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <code class="small text-muted">{{ $migration['name'] }}</code>
                                                @if($canManageModules)
                                                    <form method="POST" action="{{ route('modules.migrations.run', ['slug' => $module['slug'], 'migration' => $migration['name']]) }}" class="module-action-form">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                            data-confirm="Jalankan migration {{ $migration['name'] }} untuk modul {{ $module['name'] }}?"
                                                            data-loading="Running...">
                                                            Run
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
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
                                    @if($canManageModules)
                                        <form method="POST" action="{{ route('modules.db-update', $module['slug']) }}" class="module-action-form">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-orange"
                                                data-confirm="Jalankan pending DB update untuk modul {{ $module['name'] }}? Hanya migration modul ini yang akan dijalankan."
                                                data-loading="Running DB Update...">
                                                Run DB Update
                                            </button>
                                        </form>
                                    @endif
                                @elseif($module['installed'] && $module['has_filesystem_issues'])
                                    <span class="text-danger small text-end">Perbaiki path/casing dulu sebelum DB update.</span>
                                @endif

                                @if(!$module['installed'])
                                    @if($canManageModules)
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
                                    @endif
                                @elseif(!$module['active'])
                                    @if($canManageModules)
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
                                    @endif
                                @else
                                    @if($canManageModules)
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
                                    @endif
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
                        <td colspan="8" class="text-center text-muted py-5">
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
    const selectAllCheckbox = document.getElementById('modules-select-all');
    const bulkCheckboxes = Array.from(document.querySelectorAll('.module-bulk-checkbox'));
    const bulkActionSelect = document.getElementById('modules-bulk-action');
    const bulkRunButton = document.getElementById('modules-bulk-run');
    const bulkPanel = document.getElementById('modules-bulk-panel');
    const bulkStopButton = document.getElementById('modules-bulk-stop');
    const bulkRefreshButton = document.getElementById('modules-bulk-refresh');
    const bulkQueueList = document.getElementById('modules-bulk-queue');
    const bulkQueueSummary = document.getElementById('modules-bulk-queue-summary');
    const bulkProgressText = document.getElementById('modules-bulk-progress-text');
    const bulkProgressBar = document.getElementById('modules-bulk-progress-bar');
    const bulkSummary = document.getElementById('modules-bulk-summary');
    const bulkCurrent = document.getElementById('modules-bulk-current');
    const bulkCurrentDetail = document.getElementById('modules-bulk-current-detail');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const moduleRegistry = @json($moduleRegistry);
    const moduleState = Object.fromEntries(moduleRegistry.map((module) => [module.slug, { ...module }]));
    const moduleRows = Object.fromEntries(Array.from(document.querySelectorAll('[data-module-row]')).map((row) => [row.dataset.moduleSlug, row]));
    const actionLabels = {
        install: 'Install',
        activate: 'Activate',
        deactivate: 'Deactivate',
        'db-update': 'DB Update',
    };
    const actionRoutes = {
        install: @json(route('modules.install', ['slug' => '__SLUG__'])),
        activate: @json(route('modules.activate', ['slug' => '__SLUG__'])),
        deactivate: @json(route('modules.deactivate', ['slug' => '__SLUG__'])),
        'db-update': @json(route('modules.db-update', ['slug' => '__SLUG__'])),
    };

    let shouldStopBulk = false;
    let bulkRunning = false;

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[char]));
    }

    function updateSelectAllState() {
        if (!selectAllCheckbox) return;
        const checkedCount = bulkCheckboxes.filter((item) => item.checked).length;
        selectAllCheckbox.checked = checkedCount > 0 && checkedCount === bulkCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < bulkCheckboxes.length;
    }

    function selectedSlugs() {
        return bulkCheckboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
    }

    function getModule(slug) {
        const module = moduleState[slug];
        if (!module) {
            throw new Error(`Module '${slug}' tidak ditemukan.`);
        }

        return module;
    }

    function expandWithDependencies(slugs) {
        const resolved = new Set();
        const stack = [...slugs];

        while (stack.length > 0) {
            const slug = stack.pop();
            if (!slug || resolved.has(slug)) {
                continue;
            }

            const module = getModule(slug);
            resolved.add(slug);

            (module.requires || []).forEach((requiredSlug) => {
                if (!resolved.has(requiredSlug)) {
                    stack.push(requiredSlug);
                }
            });
        }

        return Array.from(resolved);
    }

    function topologicalOrder(slugs) {
        const selected = new Set(slugs);
        const visited = new Set();
        const visiting = new Set();
        const ordered = [];

        const visit = (slug) => {
            if (visited.has(slug)) {
                return;
            }

            if (visiting.has(slug)) {
                throw new Error(`Circular dependency terdeteksi pada module '${slug}'.`);
            }

            const module = getModule(slug);
            visiting.add(slug);

            (module.requires || []).forEach((requiredSlug) => {
                if (selected.has(requiredSlug)) {
                    visit(requiredSlug);
                }
            });

            visiting.delete(slug);
            visited.add(slug);
            ordered.push(slug);
        };

        slugs.forEach((slug) => visit(slug));

        return ordered;
    }

    function assertNoExternalActiveDependents(slugs) {
        const selected = new Set(slugs);
        const blocked = [];

        slugs.forEach((slug) => {
            const dependents = Object.values(moduleState)
                .filter((module) => module.active && (module.requires || []).includes(slug) && !selected.has(module.slug))
                .map((module) => module.slug);

            if (dependents.length > 0) {
                blocked.push(`${slug} dipakai oleh ${dependents.join(', ')}`);
            }
        });

        if (blocked.length > 0) {
            throw new Error(`Bulk deactivate diblokir: ${blocked.join('; ')}. Pilih module dependent-nya juga atau nonaktifkan satu per satu.`);
        }
    }

    function createQueue(action, slugs) {
        if (slugs.length < 1) {
            throw new Error('Pilih minimal satu module terlebih dahulu.');
        }

        if (!actionLabels[action]) {
            throw new Error('Pilih bulk action terlebih dahulu.');
        }

        if (action === 'deactivate') {
            assertNoExternalActiveDependents(slugs);

            return topologicalOrder(slugs)
                .reverse()
                .filter((slug) => getModule(slug).active)
                .map((slug) => ({ slug, action: 'deactivate' }));
        }

        const expanded = expandWithDependencies(slugs);
        const ordered = topologicalOrder(expanded);
        const queue = [];

        ordered.forEach((slug) => {
            const module = getModule(slug);

            if (action === 'install') {
                if (!module.installed) {
                    queue.push({ slug, action: 'install' });
                }
                return;
            }

            if (action === 'activate') {
                if (!module.installed) {
                    queue.push({ slug, action: 'install' });
                }
                if (!module.active) {
                    queue.push({ slug, action: 'activate' });
                }
                return;
            }

            if (action === 'db-update' && module.installed && module.has_pending_db_update) {
                queue.push({ slug, action: 'db-update' });
            }
        });

        return queue;
    }

    function renderQueue(queue, completed = 0, failedIndex = -1) {
        if (!bulkPanel || !bulkQueueList || !bulkQueueSummary || !bulkProgressText || !bulkProgressBar) {
            return;
        }

        bulkPanel.hidden = false;
        const total = queue.length;
        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

        bulkProgressText.textContent = `${completed} / ${total}`;
        bulkProgressBar.style.width = `${percent}%`;
        bulkProgressBar.classList.toggle('progress-bar-striped', bulkRunning);
        bulkProgressBar.classList.toggle('progress-bar-animated', bulkRunning);

        const waitingCount = Math.max(total - completed - (failedIndex >= 0 ? 1 : 0), 0);
        bulkQueueSummary.textContent = total < 1
            ? 'Tidak ada item yang perlu diproses.'
            : `${total} step, ${completed} selesai, ${waitingCount} menunggu${failedIndex >= 0 ? ', 1 gagal' : ''}.`;

        bulkQueueList.innerHTML = queue.map((item, index) => {
            const state = index < completed
                ? 'success'
                : (index === failedIndex ? 'danger' : (bulkRunning && index === completed ? 'warning' : 'secondary'));
            const stateLabel = index < completed
                ? 'Done'
                : (index === failedIndex ? 'Failed' : (bulkRunning && index === completed ? 'Running' : 'Queued'));

            return `
                <li class="list-group-item px-0 d-flex align-items-center justify-content-between gap-2">
                    <div class="min-w-0">
                        <div class="fw-semibold">${escapeHtml(actionLabels[item.action])} <span class="text-muted">/</span> ${escapeHtml(item.slug)}</div>
                        ${item.requested ? `<div class="small text-muted">${escapeHtml(item.requested)}</div>` : ''}
                    </div>
                    <span class="badge bg-${state}-lt text-${state}">${stateLabel}</span>
                </li>
            `;
        }).join('');
    }

    function updateModuleRow(slug) {
        const row = moduleRows[slug];
        const module = moduleState[slug];
        if (!row || !module) {
            return;
        }

        const statusEl = row.querySelector('[data-module-status]');
        const dbStatusEl = row.querySelector('[data-module-db-status]');

        if (statusEl) {
            if (!module.installed) {
                statusEl.className = 'badge bg-secondary-lt text-secondary';
                statusEl.textContent = 'Not Installed';
            } else if (module.active) {
                statusEl.className = 'badge bg-success-lt text-success';
                statusEl.textContent = 'Active';
            } else {
                statusEl.className = 'badge bg-warning-lt text-warning';
                statusEl.textContent = 'Installed';
            }
        }

        if (dbStatusEl) {
            if (!module.installed) {
                dbStatusEl.className = 'badge bg-secondary-lt text-secondary';
                dbStatusEl.textContent = '-';
            } else if (module.has_filesystem_issues) {
                dbStatusEl.className = 'badge bg-red-lt text-red';
                dbStatusEl.textContent = 'Filesystem Issue';
            } else if (module.has_pending_db_update) {
                dbStatusEl.className = 'badge bg-orange-lt text-orange';
                dbStatusEl.textContent = 'Pending DB Update';
            } else {
                dbStatusEl.className = 'badge bg-success-lt text-success';
                dbStatusEl.textContent = 'Up to Date';
            }
        }
    }

    function applyStepResult(step) {
        const module = getModule(step.slug);

        if (step.action === 'install') {
            module.installed = true;
        }

        if (step.action === 'activate') {
            module.installed = true;
            module.active = true;
        }

        if (step.action === 'deactivate') {
            module.active = false;
        }

        if (step.action === 'db-update') {
            module.has_pending_db_update = false;
            module.last_db_update_status = 'success';
        }

        updateModuleRow(step.slug);
    }

    async function executeStep(step) {
        const response = await fetch(actionRoutes[step.action].replace('__SLUG__', encodeURIComponent(step.slug)), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({}),
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (_) {
            payload = null;
        }

        if (!response.ok || !payload?.ok) {
            throw new Error(payload?.message || `Request ${step.action} untuk module '${step.slug}' gagal.`);
        }

        return payload;
    }

    async function runBulkQueue() {
        if (!bulkActionSelect || !bulkRunButton || bulkRunning) {
            return;
        }

        const action = bulkActionSelect.value;
        const slugs = selectedSlugs();

        let queue = [];

        try {
            queue = createQueue(action, slugs);
        } catch (error) {
            window.alert(error.message);
            return;
        }

        if (queue.length < 1) {
            bulkPanel.hidden = false;
            bulkRunning = false;
            renderQueue([]);
            bulkSummary.textContent = 'Tidak ada step yang perlu dijalankan untuk pilihan saat ini.';
            bulkCurrent.textContent = 'Tidak ada proses.';
            bulkCurrentDetail.textContent = 'Semua module sudah pada state yang diminta.';
            bulkRefreshButton.hidden = false;
            return;
        }

        if (!window.confirm(`Jalankan ${queue.length} step untuk bulk ${actionLabels[action]}? Proses akan dieksekusi satu per satu.`)) {
            return;
        }

        shouldStopBulk = false;
        bulkRunning = true;
        bulkStopButton.disabled = false;
        bulkRunButton.disabled = true;
        bulkActionSelect.disabled = true;
        bulkCheckboxes.forEach((checkbox) => {
            checkbox.disabled = true;
        });
        if (selectAllCheckbox) {
            selectAllCheckbox.disabled = true;
        }

        bulkStopButton.hidden = false;
        bulkRefreshButton.hidden = true;
        bulkSummary.textContent = `Menyiapkan ${queue.length} step.`;
        bulkCurrent.textContent = 'Mulai eksekusi...';
        bulkCurrentDetail.textContent = 'Dependency diurutkan otomatis sebelum request dijalankan.';
        renderQueue(queue, 0, -1);

        let completed = 0;
        let failedIndex = -1;

        for (let index = 0; index < queue.length; index += 1) {
            if (shouldStopBulk) {
                bulkSummary.textContent = `Dihentikan setelah ${completed} step selesai.`;
                break;
            }

            const step = queue[index];
            bulkCurrent.textContent = `${actionLabels[step.action]} / ${step.slug}`;
            bulkCurrentDetail.textContent = `Step ${index + 1} dari ${queue.length} sedang diproses.`;
            renderQueue(queue, completed, -1);

            try {
                const payload = await executeStep(step);
                applyStepResult(step);
                completed += 1;
                bulkSummary.textContent = payload.message || `${actionLabels[step.action]} ${step.slug} selesai.`;
                renderQueue(queue, completed, -1);
            } catch (error) {
                failedIndex = index;
                bulkSummary.textContent = error.message;
                bulkCurrent.textContent = `${actionLabels[step.action]} / ${step.slug}`;
                bulkCurrentDetail.textContent = 'Proses dihentikan karena ada step yang gagal.';
                renderQueue(queue, completed, failedIndex);
                break;
            }
        }

        bulkRunning = false;
        bulkRunButton.disabled = false;
        bulkActionSelect.disabled = false;
        bulkCheckboxes.forEach((checkbox) => {
            checkbox.disabled = false;
        });
        if (selectAllCheckbox) {
            selectAllCheckbox.disabled = false;
        }

        bulkStopButton.hidden = true;
        bulkStopButton.disabled = false;
        bulkRefreshButton.hidden = false;
        bulkProgressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');

        if (failedIndex < 0 && !shouldStopBulk) {
            bulkCurrent.textContent = 'Selesai';
            bulkCurrentDetail.textContent = `${completed} step berhasil dijalankan. Refresh page untuk sinkronkan statistik dan tombol aksi.`;
            renderQueue(queue, completed, -1);
        } else if (shouldStopBulk && failedIndex < 0) {
            bulkCurrent.textContent = 'Dihentikan';
            bulkCurrentDetail.textContent = 'Tidak ada step baru yang dijalankan setelah item terakhir selesai.';
            renderQueue(queue, completed, -1);
        }

        updateSelectAllState();
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            bulkCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }

    bulkCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateSelectAllState);
    });

    bulkRunButton?.addEventListener('click', runBulkQueue);

    bulkStopButton?.addEventListener('click', function () {
        shouldStopBulk = true;
        bulkStopButton.disabled = true;
        bulkSummary.textContent = 'Stop diminta. Queue akan berhenti setelah step yang sedang berjalan selesai.';
    });

    bulkRefreshButton?.addEventListener('click', function () {
        window.location.reload();
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-loading]');
        if (!btn || !btn.closest('.module-action-form')) return;
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

