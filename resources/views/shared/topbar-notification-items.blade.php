@forelse(($topbarNotifications ?? collect()) as $row)
    @php
        $notification = $row->notification;
        $tone = match($notification->severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'success' => 'success',
            default => 'primary',
        };
    @endphp
    <a href="{{ data_get($notification->actions, '0.url', route('notifications.index')) }}" class="dropdown-item px-3 py-3 border-bottom text-wrap">
        <div class="d-flex gap-3 align-items-start">
            <span class="badge bg-{{ $tone }}-lt text-{{ $tone }} mt-1">{{ strtoupper(substr($notification->severity, 0, 1)) }}</span>
            <div class="min-w-0 flex-fill">
                <div class="d-flex justify-content-between gap-2">
                    <div class="fw-semibold text-truncate">{{ $notification->title }}</div>
                    <div class="small text-muted text-nowrap">{{ optional($notification->last_seen_at)->diffForHumans() }}</div>
                </div>
                <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($notification->body, 110) }}</div>
                @if(!$row->is_read)
                    <div class="small text-primary mt-1">Belum dibaca</div>
                @endif
            </div>
        </div>
    </a>
@empty
    <div class="px-3 py-4 text-center text-muted small">
        Belum ada notifikasi baru.
    </div>
@endforelse
