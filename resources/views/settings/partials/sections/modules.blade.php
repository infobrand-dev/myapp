<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Module Terdaftar</div>
                <div class="fs-1 fw-bold mt-2">{{ $allModules->count() }}</div>
                <div class="text-muted small mt-2">Semua module yang dikenal registry.</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Terpasang</div>
                <div class="fs-1 fw-bold mt-2">{{ $installedModules->count() }}</div>
                <div class="text-muted small mt-2">Sudah terpasang untuk tenant app.</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Aktif</div>
                <div class="fs-1 fw-bold mt-2 text-success">{{ $activeModules->count() }}</div>
                <div class="text-muted small mt-2">Berjalan aktif di shell aplikasi.</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">Status Modul & Entitlement</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Modul</th>
                    <th>Kategori</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allModules as $module)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $module['name'] }}</div>
                            <div class="text-muted small">{{ $module['slug'] }}</div>
                        </td>
                        <td>{{ \Illuminate\Support\Str::headline($module['category']) }}</td>
                        <td>
                            @if(!$module['installed'])
                                <span class="badge bg-secondary-lt text-secondary">Belum terpasang</span>
                            @elseif($module['active'])
                                <span class="badge bg-success-lt text-success">Aktif</span>
                            @else
                                <span class="badge bg-warning-lt text-warning">Terpasang</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">Belum ada module pada registry.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
