<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">User</div>
                <div class="fs-1 fw-bold mt-2">{{ $users->count() }}</div>
                <div class="text-muted small mt-2">Semua user pada tenant aktif.</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Role</div>
                <div class="fs-1 fw-bold mt-2">{{ $roles->count() }}</div>
                <div class="text-muted small mt-2">Role tenant-scoped dari Spatie teams.</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Company Aktif</div>
                <div class="fs-3 fw-bold mt-2">{{ optional($currentCompany)->code ?: '-' }}</div>
                <div class="text-muted small mt-2">Access company dan branch sekarang dikelola dari halaman Users.</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Branch Aktif</div>
                <div class="fs-3 fw-bold mt-2">{{ optional($currentBranch)->code ?: '-' }}</div>
                <div class="text-muted small mt-2">Branch tetap optional pada runtime scope.</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-3">
        <div>
            <h3 class="card-title mb-0">User Tenant</h3>
            <div class="text-muted small mt-1">Role menentukan aksi. Company dan branch access menentukan scope operasional user.</div>
        </div>
        @can('users.view')
            <a href="{{ route('users.index') }}" class="btn btn-outline-primary btn-sm">Kelola User</a>
        @endcan
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @php $roleNames = $user->getRoleNames(); @endphp
                            @if($roleNames->isEmpty())
                                <span class="text-muted small">Belum ada role</span>
                            @else
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($roleNames as $roleName)
                                        <span class="badge bg-blue-lt text-blue">{{ $roleName }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">Belum ada user pada tenant ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
