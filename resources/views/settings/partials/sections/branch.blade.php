<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">{{ $editingBranch ? 'Edit Branch' : 'Tambah Branch' }}</h3>
            </div>
            <div class="card-body">
                @if(!$currentCompany)
                    <div class="alert alert-warning mb-0">Pilih company aktif terlebih dahulu sebelum membuat atau mengelola branch.</div>
                @else
                    @php
                        $branchFormAction = $editingBranch ? route('settings.branch.update', $editingBranch) : route('settings.branch.store');
                    @endphp
                    <form method="POST" action="{{ $branchFormAction }}" class="row g-3">
                        @csrf
                        @if($editingBranch)
                            @method('PUT')
                        @endif
                        <div class="col-12">
                            <label class="form-label">Company aktif</label>
                            <input type="text" class="form-control" value="{{ $currentCompany->name }}" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', optional($editingBranch)->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug', optional($editingBranch)->slug) }}" placeholder="auto dari nama">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kode</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', optional($editingBranch)->code) }}">
                        </div>
                        <div class="col-12">
                            <input type="hidden" name="is_active" value="0">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingBranch ? $editingBranch->is_active : true))>
                                <span class="form-check-label">Branch aktif</span>
                            </label>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            @can('settings.manage')
                                <button class="btn btn-primary" type="submit">{{ $editingBranch ? 'Update Branch' : 'Buat Branch' }}</button>
                            @endcan
                            @if($editingBranch)
                                <a href="{{ route('settings.branch') }}" class="btn btn-outline-secondary">Batal</a>
                            @endif
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="card-title mb-0">Direktori Branch</h3>
                    <div class="text-muted small mt-1">Daftar branch mengikuti company aktif dan menyediakan switch untuk runtime scope branch.</div>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-azure-lt text-azure">{{ optional($currentCompany)->name ?? 'Belum ada company dipilih' }}</span>
                    @if($currentBranch)
                        <form method="POST" action="{{ route('settings.branch.clear') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Hapus Pilihan Branch</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $branch->name }}</div>
                                    <div class="text-muted small">{{ $branch->slug }}{{ $branch->code ? ' · ' . $branch->code : '' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $branch->is_active ? 'success' : 'secondary' }}-lt text-{{ $branch->is_active ? 'success' : 'secondary' }}">
                                        {{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    @if(optional($currentBranch)->id === $branch->id)
                                        <span class="badge bg-primary text-white">Sedang digunakan</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="table-actions justify-content-end">
                                        @if($branch->is_active)
                                            <form method="POST" action="{{ route('settings.branch.switch', $branch) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Gunakan</button>
                                            </form>
                                        @endif
                                        @can('settings.manage')
                                            <a href="{{ route('settings.branch', ['edit' => $branch->id]) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            @if(!$branch->is_active)
                                                <form method="POST" action="{{ route('settings.branch.activate', $branch) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Aktifkan</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-5">Belum ada branch untuk company aktif saat ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
