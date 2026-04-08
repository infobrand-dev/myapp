@php
    $money = app(\App\Support\MoneyFormatter::class);
    $net = (float) ($metrics['net_month'] ?? 0);
@endphp

<div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-kpi p-3 p-lg-4 h-100 d-flex flex-column">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="text-secondary text-uppercase small fw-bold">Finance</div>
                <div class="mt-2 fw-bold" style="font-size: 2rem; line-height: 1; color: var(--db-ink);">{{ $metrics['entry_count'] ?? 0 }}</div>
                <div class="text-muted small mt-1">transaksi finance bulan ini</div>
            </div>
            <span class="badge {{ $net >= 0 ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }} flex-shrink-0">
                {{ $net >= 0 ? 'Net +' : 'Net -' }}
            </span>
        </div>

        <div class="mt-auto pt-3 d-grid gap-1">
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Cash in</span>
                <strong class="text-green">{{ $money->format((float) ($metrics['cash_in_month'] ?? 0), 'IDR') }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Cash out</span>
                <strong class="text-orange">{{ $money->format((float) ($metrics['cash_out_month'] ?? 0), 'IDR') }}</strong>
            </div>
            @if(Route::has('finance.transactions.index'))
                <a href="{{ route('finance.transactions.index') }}" class="btn btn-sm btn-ghost-secondary mt-1 w-100">
                    Buka Finance <i class="ti ti-arrow-right ms-1"></i>
                </a>
            @endif
        </div>
    </div>
</div>
