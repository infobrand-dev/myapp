@php
    $total = max(1, ($metrics['open'] ?? 0) + ($metrics['claimed'] ?? 0));
    $openShare = min(100, round((($metrics['open'] ?? 0) / $total) * 100));
    $claimedShare = min(100, round((($metrics['claimed'] ?? 0) / $total) * 100));
    $widgetConfig = [
        'openShare' => $openShare,
        'claimedShare' => $claimedShare,
    ];
@endphp

<div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-kpi p-3 p-lg-4 h-100 conversation-dashboard-card" data-conversation-dashboard-card>
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="text-secondary text-uppercase small fw-bold">Percakapan</div>
                <div class="mt-2 fw-bold" style="font-size: 2rem; line-height: 1; color: var(--db-ink);">{{ $metrics['unread'] ?? 0 }}</div>
                <div class="text-muted small mt-1">
                    {{ ($metrics['audience'] ?? 'global') === 'global'
                        ? 'pesan belum dibaca di inbox'
                        : 'pesan belum dibaca milik Anda' }}
                </div>
            </div>
            <span class="badge bg-red-lt text-red">Live</span>
        </div>

        <div class="mt-3 d-grid gap-2">
            <div>
                <div class="d-flex align-items-center justify-content-between small mb-1">
                    <span class="text-muted">Terbuka</span>
                    <strong>{{ $metrics['open'] ?? 0 }}</strong>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-primary" data-conversation-open style="width: 0%"></div>
                </div>
            </div>
            <div>
                <div class="d-flex align-items-center justify-content-between small mb-1">
                    <span class="text-muted">Diklaim</span>
                    <strong>{{ $metrics['claimed'] ?? 0 }}</strong>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-green" data-conversation-claimed style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-conversation-dashboard-config>@json($widgetConfig)</script>
<script src="{{ mix('js/modules/conversations/dashboard-card.js') }}" defer></script>
