@php
    $pct = ($limit > 0) ? min(100, (int) round($total / $limit * 100)) : null;
    $barColor = ($pct >= 90) ? 'bg-red' : (($pct >= 70) ? 'bg-orange' : 'bg-primary');
@endphp
<div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-kpi p-3 p-lg-4 h-100 d-flex flex-column">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="text-secondary text-uppercase small fw-bold">Contacts</div>
                <div class="mt-2 fw-bold" style="font-size: 2rem; line-height: 1; color: var(--db-ink);">{{ number_format($total) }}</div>
                <div class="text-muted small mt-1">
                    @if($limit !== null)
                        dari {{ number_format($limit) }} kuota
                    @else
                        kontak tersimpan
                    @endif
                </div>
            </div>
            <span class="badge bg-primary-lt text-primary flex-shrink-0">Contacts</span>
        </div>

        @if($limit !== null && $pct !== null)
            <div class="progress progress-sm my-2">
                <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
            </div>
            <div class="small text-muted">{{ $pct }}% terpakai</div>
        @endif

        <div class="mt-auto pt-2 d-flex justify-content-between align-items-center small">
            <span class="text-muted">Baru bulan ini: <strong class="text-body">{{ $newThisMonth }}</strong></span>
            @if(Route::has('contacts.index'))
                <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-ghost-secondary">
                    <i class="ti ti-arrow-right"></i>
                </a>
            @endif
        </div>
    </div>
</div>
