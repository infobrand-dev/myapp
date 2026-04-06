<div class="row g-3">
    <div class="col-12">
        <form method="POST" action="{{ route('settings.general.save') }}">
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profil Workspace</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Workspace <span class="text-danger">*</span></label>
                            <input type="text" name="workspace_name"
                                   class="form-control @error('workspace_name') is-invalid @enderror"
                                   value="{{ old('workspace_name', optional($tenant)->name ?? '') }}" required>
                            @error('workspace_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Alamat Workspace</label>
                            <input type="text" class="form-control bg-body-secondary"
                                   value="{{ optional($tenant)->slug ?? '-' }}" readonly>
                            <div class="form-hint">Tidak dapat diubah setelah workspace dibuat.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="mt-1">
                                @if(optional($tenant)->is_active ?? true)
                                    <span class="badge bg-green-lt text-green">Aktif</span>
                                @else
                                    <span class="badge bg-red-lt text-red">Nonaktif</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Paket Aktif</label>
                            <div class="fw-semibold mt-1">
                                {{ optional($plan)->display_name ?? optional($plan)->name ?? '-' }}
                            </div>
                            @if(!optional($plan)->name)
                                <div class="form-hint">Belum ada paket terpasang.</div>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <div class="d-flex gap-2">
                                    <i class="ti ti-info-circle flex-shrink-0 mt-1"></i>
                                    <div>Halaman ini untuk ringkasan workspace dan pengaturan dasar yang aman. Pengaturan teknis yang berisiko mengubah transaksi atau laporan dikelola di bagian lain.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
