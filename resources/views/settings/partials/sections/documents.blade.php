<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Invoice Numbering</h3>
            </div>
            <div class="card-body">
                <div class="text-muted small">Area ini disiapkan untuk prefix, sequence, reset period, dan fallback numbering per company atau branch.</div>
                <div class="mt-4 d-flex flex-column gap-3">
                    <div class="border rounded-3 p-3">
                        <div class="text-secondary small text-uppercase fw-bold">Scope</div>
                        <div class="fw-semibold mt-1">{{ optional($currentCompany)->name ?? 'Company-level settings' }}</div>
                        <div class="text-muted small mt-1">{{ optional($currentBranch)->name ? 'Branch override siap dilapis di atas company.' : 'Belum ada branch override aktif.' }}</div>
                    </div>
                    <div class="border rounded-3 p-3">
                        <div class="text-secondary small text-uppercase fw-bold">Status</div>
                        <div class="fw-semibold mt-1">Prepared</div>
                        <div class="text-muted small mt-1">UI settings sudah punya tempat tetap, persistence invoice config belum ditambahkan pada langkah ini.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Receipt & Document Template</h3>
            </div>
            <div class="card-body">
                <div class="text-muted small">Tempat untuk header/footer nota, legal info company, logo, catatan dokumen, dan aturan output per branch.</div>
                <ul class="list-group list-group-flush mt-3">
                    <li class="list-group-item px-0">Invoice header dan legal identity per company</li>
                    <li class="list-group-item px-0">Receipt footer per branch bila outlet membutuhkan variasi</li>
                    <li class="list-group-item px-0">Format cetak dan branding tenant-wide</li>
                </ul>
            </div>
        </div>
    </div>
</div>
