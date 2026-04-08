@php($money = app(\App\Support\MoneyFormatter::class))

<div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-kpi p-3 p-lg-4 h-100 d-flex flex-column">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="text-secondary text-uppercase small fw-bold">Payments</div>
                <div class="mt-2 fw-bold" style="font-size: 2rem; line-height: 1; color: var(--db-ink);">{{ $metrics['posted_count'] ?? 0 }}</div>
                <div class="text-muted small mt-1">payment posted bulan ini</div>
            </div>
            <span class="badge bg-green-lt text-green flex-shrink-0">Cash</span>
        </div>

        <div class="mt-auto pt-3 d-grid gap-1">
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Dana masuk bulan ini</span>
                <strong>{{ $money->format((float) ($metrics['collected_month'] ?? 0), 'IDR') }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Pending</span>
                <strong class="{{ ($metrics['pending_count'] ?? 0) > 0 ? 'text-orange' : 'text-muted' }}">
                    {{ $metrics['pending_count'] ?? 0 }}
                </strong>
            </div>
            @if(Route::has('payments.index'))
                <a href="{{ route('payments.index') }}" class="btn btn-sm btn-ghost-secondary mt-1 w-100">
                    Buka Payments <i class="ti ti-arrow-right ms-1"></i>
                </a>
            @endif
        </div>
    </div>
</div>
