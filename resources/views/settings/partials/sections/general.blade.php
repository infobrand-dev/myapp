<div class="row g-3">

    {{-- Kiri: Form Profil Workspace --}}
    <div class="col-lg-7">
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

                        <div class="col-md-6">
                            <label class="form-label">Mata Uang Workspace</label>
                            <select name="default_currency"
                                    class="form-select @error('default_currency') is-invalid @enderror"
                                    {{ $currencySettingsLocked ? 'disabled' : '' }}>
                                @foreach($currencyOptions as $code => $label)
                                    <option value="{{ $code }}" @selected(old('default_currency', $defaultCurrency) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if($currencySettingsLocked)
                                <input type="hidden" name="default_currency" value="{{ old('default_currency', $defaultCurrency) }}">
                                <div class="form-hint">Dikunci — tidak dapat diubah setelah ada transaksi.</div>
                            @endif
                            @error('default_currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($currentCompany)
                            <div class="col-md-6">
                                <label class="form-label">Mata Uang Company Aktif</label>
                                <select name="company_default_currency"
                                        class="form-select @error('company_default_currency') is-invalid @enderror"
                                        {{ $currencySettingsLocked ? 'disabled' : '' }}>
                                    <option value="">Ikuti Workspace</option>
                                    @foreach($currencyOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(old('company_default_currency', $companyDefaultCurrency) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if($currencySettingsLocked)
                                    <input type="hidden" name="company_default_currency" value="{{ old('company_default_currency', $companyDefaultCurrency) }}">
                                @endif
                                @error('company_default_currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

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

    {{-- Kanan: Info Mata Uang & Ringkasan --}}
    <div class="col-lg-5 d-flex flex-column gap-3">

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Mata Uang Default</h3>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <div>
                    <div class="text-secondary text-uppercase small fw-bold mb-1">Workspace</div>
                    <div class="fw-semibold">{{ $currencyOptions[$defaultCurrency] ?? $defaultCurrency }}</div>
                </div>
                @if($currentCompany)
                    <div>
                        <div class="text-secondary text-uppercase small fw-bold mb-1">Company Aktif</div>
                        <div class="fw-semibold">{{ $currencyOptions[$companyDefaultCurrency] ?? ($companyDefaultCurrency ?: $defaultCurrency) }}</div>
                    </div>
                @endif
                @if($currencySettingsLocked)
                    <div class="alert alert-warning mb-0">
                        <div class="d-flex gap-2">
                            <i class="ti ti-lock flex-shrink-0 mt-1"></i>
                            <div class="small">Mata uang dikunci setelah setup awal agar transaksi, laporan, dan angka historis tidak berubah. Jika perlu diubah, lakukan lewat intervensi platform.</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ringkasan Scope</h3>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                @forelse($settingsStats as $stat)
                    <div>
                        <div class="text-secondary text-uppercase small fw-bold">{{ $stat['label'] }}</div>
                        <div class="fs-2 fw-bold mt-1">{{ $stat['value'] }}</div>
                        <div class="text-muted small mt-1">{{ $stat['meta'] }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada data.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
