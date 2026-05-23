@php
    $unreadCount = $topbarNotificationCount ?? 0;
@endphp
<div class="dropdown notification-center-dropdown topbar-notification-center">
    <button
        type="button"
        class="btn btn-icon btn-outline-secondary position-relative"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        aria-label="Notifications"
    >
        <i class="ti ti-bell"></i>
        <span
            class="badge bg-red text-white notification-center-badge {{ $unreadCount > 0 ? '' : 'd-none' }}"
            data-notification-count
        >
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow-sm p-0 notification-center-menu">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <div>
                <div class="fw-semibold">Notifications</div>
                <div class="small text-muted">Ringkasan notifikasi terbaru</div>
            </div>
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">Buka Inbox</a>
        </div>
        <div class="notification-center-list" data-notification-preview>
            @include('shared.topbar-notification-items', ['topbarNotifications' => $topbarNotifications ?? collect()])
        </div>
    </div>
</div>
