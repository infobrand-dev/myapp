<div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-kpi p-3 p-lg-4 h-100 d-flex flex-column">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="text-secondary text-uppercase small fw-bold">WhatsApp Web</div>
                <div class="mt-2 fw-bold" style="font-size: 2rem; line-height: 1; color: var(--db-ink);">{{ $connected }}</div>
                <div class="text-muted small mt-1">sesi terhubung dari {{ $total }} aktif</div>
            </div>
            <span class="badge {{ $connected > 0 ? 'bg-green-lt text-green' : ($total > 0 ? 'bg-orange-lt text-orange' : 'bg-secondary-lt text-secondary') }} flex-shrink-0">
                {{ $connected > 0 ? 'Online' : ($total > 0 ? 'Terputus' : 'Belum ada') }}
            </span>
        </div>

        <div class="mt-auto pt-3">
            @if(Route::has('whatsappweb.index'))
                <a href="{{ route('whatsappweb.index') }}" class="btn btn-sm btn-ghost-secondary w-100">
                    Buka Panel <i class="ti ti-arrow-right ms-1"></i>
                </a>
            @endif
        </div>
    </div>
</div>
