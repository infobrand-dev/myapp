<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-3">
        <div>
            <h3 class="card-title mb-0">Company Directory</h3>
            <div class="text-muted small mt-1">UI ini menjadi titik masuk tenant untuk mengelola business entity internal. CRUD belum ditambahkan di langkah ini, tetapi struktur halaman dan listing tenant-wide sudah siap.</div>
        </div>
        <span class="badge bg-blue-lt text-blue">Tenant scope</span>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Code</th>
                    <th>Branches</th>
                    <th>Status</th>
                    <th>Context</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $company->name }}</div>
                            <div class="text-muted small">{{ $company->slug }}</div>
                        </td>
                        <td>{{ $company->code ?: '-' }}</td>
                        <td>{{ $company->active_branches_count }}/{{ $company->branches_count }}</td>
                        <td>
                            <span class="badge bg-{{ $company->is_active ? 'success' : 'secondary' }}-lt text-{{ $company->is_active ? 'success' : 'secondary' }}">
                                {{ $company->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            @if(optional($currentCompany)->id === $company->id)
                                <span class="badge bg-primary text-white">Current company</span>
                            @else
                                <span class="text-muted small">Available in tenant</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">Belum ada company di tenant ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
