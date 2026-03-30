<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center flex-wrap gap-3 gap-md-5">
            <div>
                <div class="text-muted text-uppercase small fw-bold mb-1">Tenant</div>
                <div class="fw-bold">{{ optional($tenant)->name ?? 'Default tenant' }}</div>
                <div class="text-muted" style="font-size:.75rem;">{{ optional($tenant)->slug ?? 'default-tenant' }}</div>
            </div>
            <div class="ctx-switcher-divider d-none d-sm-block" style="height:2.5rem;"></div>
            <div>
                <div class="text-muted text-uppercase small fw-bold mb-1">Company Aktif</div>
                @if(optional($currentCompany)->name)
                    <div class="fw-bold">{{ $currentCompany->name }}</div>
                    <div class="text-muted" style="font-size:.75rem;">{{ $currentCompany->code ?? '' }}</div>
                @else
                    <div class="text-muted fst-italic" style="font-size:.875rem;">Belum ada company aktif</div>
                @endif
            </div>
            <div class="ctx-switcher-divider d-none d-sm-block" style="height:2.5rem;"></div>
            <div>
                <div class="text-muted text-uppercase small fw-bold mb-1">Branch Aktif</div>
                @if(optional($currentBranch)->name)
                    <div class="fw-bold">{{ $currentBranch->name }}</div>
                    <div class="text-muted" style="font-size:.75rem;">{{ $currentBranch->code ?? '' }}</div>
                @else
                    <div class="text-muted fst-italic" style="font-size:.875rem;">Tidak memilih branch</div>
                @endif
            </div>
        </div>
    </div>
</div>
