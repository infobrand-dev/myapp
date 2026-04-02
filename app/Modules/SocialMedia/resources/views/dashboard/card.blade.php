<div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-kpi p-3 p-lg-4 h-100 d-flex flex-column">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="text-secondary text-uppercase small fw-bold">Instagram / Facebook DM</div>
                <div class="mt-2 fw-bold" style="font-size: 2rem; line-height: 1; color: var(--db-ink);">{{ $connected }}</div>
                <div class="text-muted small mt-1">
                    akun terhubung
                    @if($limit !== null)
                        dari {{ number_format($limit) }}
                    @endif
                </div>
            </div>
            <span class="badge {{ $connected > 0 ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }} flex-shrink-0">
                {{ $connected > 0 ? 'Aktif' : 'Belum ada' }}
            </span>
        </div>

        <div class="mt-auto pt-3 d-flex justify-content-between align-items-center small">
            <span class="text-muted">Total: <strong class="text-body">{{ $total }}</strong> akun</span>
            @if(Route::has('social-media.index'))
                <a href="{{ route('social-media.index') }}" class="btn btn-sm btn-ghost-secondary">
                    <i class="ti ti-arrow-right"></i>
                </a>
            @endif
        </div>
    </div>
</div>
