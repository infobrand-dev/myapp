@extends('layouts.tenant')

@section('title', 'Notifications')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Notifications</h1>
        <div class="text-muted">Inbox notifikasi lintas module untuk context aktif Anda.</div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" id="enable-push-btn" data-vapid-key="{{ config('notifications.push.vapid.public_key') }}">
            <i class="ti ti-device-mobile-message me-1"></i>Aktifkan Web Push
        </button>
        <a href="{{ route('notifications.index', ['unread' => 1]) }}" class="btn btn-primary">
            <i class="ti ti-bell-ringing me-1"></i>{{ $unreadCount }} unread
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold mb-1">Quick Summary</div>
                <div class="h2 mb-2">{{ $unreadCount }}</div>
                <div class="text-muted">notifikasi belum dibaca di inbox Anda.</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-body">
                <form method="GET" action="{{ route('notifications.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Module</label>
                        <input type="text" name="module" value="{{ $filters['module'] }}" class="form-control" placeholder="finance">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Severity</label>
                        <select name="severity" class="form-select">
                            <option value="">Semua severity</option>
                            @foreach($severityOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['severity'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="active" @selected($filters['status'] === 'active')>Active</option>
                            <option value="resolved" @selected($filters['status'] === 'resolved')>Resolved</option>
                            <option value="dismissed" @selected($filters['status'] === 'dismissed')>Dismissed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-block">Unread only</label>
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="unread" value="1" @checked($filters['unread'])>
                            <span class="form-check-label">Ya</span>
                        </label>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            @forelse($notifications as $row)
                @php
                    $notification = $row->notification;
                    $tone = match($notification->severity) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        'success' => 'success',
                        default => 'primary',
                    };
                @endphp
                <div class="list-group-item px-3 py-3 {{ $row->is_read ? '' : 'bg-blue-lt' }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between">
                        <div class="min-w-0">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="badge bg-{{ $tone }}-lt text-{{ $tone }}">{{ strtoupper($notification->severity) }}</span>
                                <span class="badge bg-secondary-lt text-secondary">{{ $notification->module }}</span>
                                @if(!$row->is_read)
                                    <span class="badge bg-primary-lt text-primary">Unread</span>
                                @endif
                                <span class="small text-muted">{{ optional($notification->last_seen_at)->diffForHumans() }}</span>
                            </div>
                            <div class="fw-semibold mb-1">{{ $notification->title }}</div>
                            <div class="text-muted">{{ $notification->body }}</div>
                            @if(!empty($notification->actions))
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    @foreach($notification->actions as $action)
                                        <a href="{{ $action['url'] }}" class="btn btn-sm btn-outline-primary">{{ $action['label'] }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="d-flex flex-lg-column align-items-start align-items-lg-end gap-2">
                            @if($row->is_read)
                                <form method="POST" action="{{ route('notifications.unread', $row->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary">Mark unread</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('notifications.read', $row->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-primary">Mark read</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('notifications.dismiss', $row->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning">Dismiss</button>
                            </form>
                            <form method="POST" action="{{ route('notifications.archive', $row->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">Archive</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item px-3 py-5 text-center text-muted">
                    Belum ada notifikasi pada filter saat ini.
                </div>
            @endforelse
        </div>
    </div>
    @if($notifications->hasPages())
        <div class="card-footer">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const button = document.getElementById('enable-push-btn');
        if (!button) return;

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
        }

        async function subscribePush() {
            if (!window.MyAppNotifier || !window.MyAppNotifier.supportsNotifications() || !window.MyAppNotifier.supportsServiceWorker()) {
                alert('Browser ini belum mendukung web push.');
                return;
            }

            const vapidKey = button.dataset.vapidKey;
            if (!vapidKey) {
                alert('VAPID public key belum dikonfigurasi.');
                return;
            }

            const granted = await window.MyAppNotifier.ensurePermission(true);
            if (!granted) {
                alert('Izin notifikasi belum diberikan.');
                return;
            }

            const registration = await window.MyAppNotifier.registerServiceWorker();
            if (!registration) {
                alert('Service worker tidak tersedia.');
                return;
            }

            let subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidKey),
                });
            }

            const response = await fetch('{{ route('notifications.push-subscriptions.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                },
                body: JSON.stringify(subscription.toJSON()),
            });

            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'Gagal mengaktifkan web push.');
            }

            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-success');
            button.innerHTML = '<i class=\"ti ti-circle-check me-1\"></i>Web Push Aktif';
        }

        button.addEventListener('click', function () {
            subscribePush().catch((error) => {
                alert(error.message || 'Gagal mengaktifkan web push.');
            });
        });
    })();
</script>
@endpush

