<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-3">
        <div>
            <h3 class="card-title mb-0">Branch Directory</h3>
            <div class="text-muted small mt-1">Daftar branch mengikuti company aktif bila ada. Ini menjaga branch tetap menjadi scope turunan dari company, bukan level yang berdiri sendiri.</div>
        </div>
        <span class="badge bg-azure-lt text-azure">{{ optional($currentCompany)->name ?? 'All tenant companies' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Company</th>
                    <th>Code</th>
                    <th>Status</th>
                    <th>Context</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $branch->name }}</div>
                            <div class="text-muted small">{{ $branch->slug }}</div>
                        </td>
                        <td>{{ optional($branch->company)->name ?? '-' }}</td>
                        <td>{{ $branch->code ?: '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $branch->is_active ? 'success' : 'secondary' }}-lt text-{{ $branch->is_active ? 'success' : 'secondary' }}">
                                {{ $branch->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            @if(optional($currentBranch)->id === $branch->id)
                                <span class="badge bg-primary text-white">Current branch</span>
                            @else
                                <span class="text-muted small">Optional runtime scope</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">Belum ada branch untuk scope company yang sedang aktif.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
