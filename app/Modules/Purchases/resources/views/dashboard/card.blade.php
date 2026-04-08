@php($money = app(\App\Support\MoneyFormatter::class))

<div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-kpi p-3 p-lg-4 h-100 d-flex flex-column">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="text-secondary text-uppercase small fw-bold">Purchases</div>
                <div class="mt-2 fw-bold" style="font-size: 2rem; line-height: 1; color: var(--db-ink);">{{ $metrics['open_count'] ?? 0 }}</div>
                <div class="text-muted small mt-1">PO aktif menunggu penerimaan penuh</div>
            </div>
            <span class="badge bg-blue-lt text-blue flex-shrink-0">Procurement</span>
        </div>

        <div class="mt-auto pt-3 d-grid gap-1">
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Belanja bulan ini</span>
                <strong>{{ $money->format((float) ($metrics['spend_month'] ?? 0), 'IDR') }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Belum lunas</span>
                <strong class="{{ ($metrics['payable_count'] ?? 0) > 0 ? 'text-orange' : 'text-muted' }}">
                    {{ $metrics['payable_count'] ?? 0 }}
                </strong>
            </div>
            @if(Route::has('purchases.index'))
                <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-ghost-secondary mt-1 w-100">
                    Buka Purchases <i class="ti ti-arrow-right ms-1"></i>
                </a>
            @endif
        </div>
    </div>
</div>
