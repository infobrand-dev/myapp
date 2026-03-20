<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-secondary text-uppercase small fw-bold">Tenant</div>
                <div class="fs-3 fw-bold mt-2">{{ optional($tenant)->name ?? 'Default tenant' }}</div>
                <div class="text-muted small mt-1">{{ optional($tenant)->slug ?? 'default-tenant' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-secondary text-uppercase small fw-bold">Active Company</div>
                <div class="fs-4 fw-semibold mt-2">{{ optional($currentCompany)->name ?? 'Belum ada company aktif' }}</div>
                <div class="text-muted small mt-1">{{ optional($currentCompany)->code ?: 'Scope company masih fallback-safe' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-secondary text-uppercase small fw-bold">Active Branch</div>
                <div class="fs-4 fw-semibold mt-2">{{ optional($currentBranch)->name ?? 'Tidak memilih branch' }}</div>
                <div class="text-muted small mt-1">{{ optional($currentBranch)->code ?: 'Branch tetap optional untuk flow company-level' }}</div>
            </div>
        </div>
    </div>
</div>
