<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Profil Workspace</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-secondary small text-uppercase fw-bold">Nama Workspace</div>
                        <div class="fw-semibold mt-1">{{ optional($tenant)->name ?? 'Default tenant' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small text-uppercase fw-bold">Slug Workspace</div>
                        <div class="fw-semibold mt-1">{{ optional($tenant)->slug ?? 'default-tenant' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small text-uppercase fw-bold">Status</div>
                        <div class="mt-1">
                            <span class="badge bg-{{ (optional($tenant)->is_active ?? true) ? 'success' : 'danger' }}-lt text-{{ (optional($tenant)->is_active ?? true) ? 'success' : 'danger' }}">
                                {{ (optional($tenant)->is_active ?? true) ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small text-uppercase fw-bold">Plan Aktif</div>
                        <div class="fw-semibold mt-1">{{ optional($plan)->name ?? 'Belum ada plan' }}</div>
                    </div>
                </div>
                <div class="alert alert-blue-lt mt-4 mb-0">
                    Halaman ini disiapkan sebagai pusat setting tenant. Profil workspace, branding, locale, timezone, dan konfigurasi global tenant selanjutnya bisa diletakkan di sini tanpa memecah UI ke banyak menu lain.
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Mata Uang Default</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.general.save') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-12">
                        <label class="form-label">Workspace Default Currency</label>
                        <select name="default_currency" class="form-select">
                            @foreach($currencyOptions as $currencyCode => $currencyLabel)
                                <option value="{{ $currencyCode }}" @selected(old('default_currency', $defaultCurrency) === $currencyCode)>{{ $currencyLabel }}</option>
                            @endforeach
                        </select>
                        <div class="form-hint">Dipakai sebagai default baru untuk CRM, Sales, Purchases, POS, dan area yang belum memilih currency secara manual.</div>
                    </div>
                    @if($currentCompany)
                        <div class="col-12">
                            <label class="form-label">Override Currency untuk Company Aktif</label>
                            <select name="company_default_currency" class="form-select">
                                <option value="">Ikuti workspace default</option>
                                @foreach($currencyOptions as $currencyCode => $currencyLabel)
                                    <option value="{{ $currencyCode }}" @selected(old('company_default_currency', $companyDefaultCurrency) === $currencyCode)>{{ $currencyLabel }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint">Opsional. Kalau diisi, company aktif memakai currency ini sebagai default draft baru.</div>
                        </div>
                    @endif
                    <div class="col-12 d-grid">
                        <button class="btn btn-primary">Simpan General Settings</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Ringkasan Scope</h3>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                @foreach($settingsStats as $stat)
                    <div class="border rounded-3 p-3">
                        <div class="text-secondary text-uppercase small fw-bold">{{ $stat['label'] }}</div>
                        <div class="fs-2 fw-bold mt-1">{{ $stat['value'] }}</div>
                        <div class="text-muted small mt-1">{{ $stat['meta'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
