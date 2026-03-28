<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">{{ $editingCompany ? 'Edit Company' : 'Tambah Company' }}</h3>
            </div>
            <div class="card-body">
                @php
                    $companyFormAction = $editingCompany ? route('settings.company.update', $editingCompany) : route('settings.company.store');
                @endphp
                <form method="POST" action="{{ $companyFormAction }}" class="row g-3">
                    @csrf
                    @if($editingCompany)
                        @method('PUT')
                    @endif
                    <div class="col-12">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', optional($editingCompany)->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', optional($editingCompany)->slug) }}" placeholder="auto dari nama">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kode</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code', optional($editingCompany)->code) }}">
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="is_active" value="0">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingCompany ? $editingCompany->is_active : true))>
                            <span class="form-check-label">Company aktif</span>
                        </label>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        @can('settings.manage')
                            <button class="btn btn-primary" type="submit">{{ $editingCompany ? 'Update Company' : 'Buat Company' }}</button>
                        @endcan
                        @if($editingCompany)
                            <a href="{{ route('settings.company') }}" class="btn btn-outline-secondary">Batal</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="card-title mb-0">Direktori Company</h3>
                    <div class="text-muted small mt-1">Kelola business entity internal tenant dan pilih context company aktif dari sini.</div>
                </div>
                <span class="badge bg-blue-lt text-blue">Tenant scope</span>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Cabang</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $company->name }}</div>
                                    <div class="text-muted small">{{ $company->slug }}{{ $company->code ? ' · ' . $company->code : '' }}</div>
                                </td>
                                <td>{{ $company->active_branches_count }}/{{ $company->branches_count }}</td>
                                <td>
                                    <span class="badge bg-{{ $company->is_active ? 'success' : 'secondary' }}-lt text-{{ $company->is_active ? 'success' : 'secondary' }}">
                                        {{ $company->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    @if(optional($currentCompany)->id === $company->id)
                                        <span class="badge bg-primary text-white">Sedang digunakan</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="table-actions justify-content-end">
                                        @if($company->is_active)
                                            <form method="POST" action="{{ route('settings.company.switch', $company) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Gunakan</button>
                                            </form>
                                        @endif
                                        @can('settings.manage')
                                            <a href="{{ route('settings.company', ['edit' => $company->id]) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            @if(!$company->is_active)
                                                <form method="POST" action="{{ route('settings.company.activate', $company) }}">
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
                                <td colspan="4" class="text-center text-muted py-5">Belum ada company di tenant ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
