<div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-kpi p-3 p-lg-4 h-100 d-flex flex-column">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="text-secondary text-uppercase small fw-bold">Pipeline</div>
                <div class="mt-2 fw-bold" style="font-size: 2rem; line-height: 1; color: var(--db-ink);">{{ $metrics['active'] }}</div>
                <div class="text-muted small mt-1">
                    {{ ($metrics['audience'] ?? 'global') === 'global' ? 'leads aktif di workspace' : 'leads aktif milik Anda' }}
                </div>
            </div>
            <span class="badge bg-green-lt text-green flex-shrink-0">CRM</span>
        </div>

        <div class="mt-auto pt-3 d-grid gap-1">
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Follow-up hari ini</span>
                <strong class="{{ $metrics['follow_up_due'] > 0 ? 'text-orange' : 'text-muted' }}">
                    {{ $metrics['follow_up_due'] }}
                </strong>
            </div>
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Berhasil bulan ini</span>
                <strong class="text-green">{{ $metrics['won_this_month'] }}</strong>
            </div>
            <a href="{{ route('crm.index') }}" class="btn btn-sm btn-ghost-secondary mt-1 w-100">
                Buka Pipeline <i class="ti ti-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>
