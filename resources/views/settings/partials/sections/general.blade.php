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
                        <div class="text-secondary small text-uppercase fw-bold">Alamat Workspace</div>
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
                        <div class="text-secondary small text-uppercase fw-bold">Paket Aktif</div>
                        <div class="fw-semibold mt-1">{{ optional($plan)->display_name ?? optional($plan)->name ?? 'Belum ada plan' }}</div>
                    </div>
                </div>
                <div class="alert alert-blue-lt mt-4 mb-0">
                    Halaman ini dipakai untuk ringkasan workspace dan pengaturan dasar yang aman. Hal teknis yang berisiko mengubah transaksi atau laporan tidak dibuka bebas dari sini.
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
                <div class="border rounded-3 p-3 mb-3">
                    <div class="text-secondary small text-uppercase fw-bold">Workspace</div>
                    <div class="fw-semibold mt-1">{{ $currencyOptions[$defaultCurrency] ?? $defaultCurrency }}</div>
                </div>

                @if($currentCompany)
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="text-secondary small text-uppercase fw-bold">Company Aktif</div>
                        <div class="fw-semibold mt-1">{{ $currencyOptions[$companyDefaultCurrency] ?? ($companyDefaultCurrency ?: $defaultCurrency) }}</div>
                    </div>
                @endif

                <div class="alert alert-warning mb-0">
                    Mata uang default dikunci setelah setup awal agar draft transaksi, laporan, dan angka historis tidak berubah arah di tengah jalan. Jika benar-benar perlu diubah, lakukan lewat intervensi platform.
                </div>
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
