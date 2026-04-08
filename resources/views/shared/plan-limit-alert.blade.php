@php
    $state = $state ?? [];
    $title = $title ?? 'Plan Limit';
    $usage = (int) ($state['usage'] ?? 0);
    $limit = $state['limit'] ?? null;
    $remaining = $state['remaining'] ?? null;
    $status = $state['status'] ?? 'ok';
    $message = $message ?? null;
    $showBadge = $showBadge ?? true;

    $tone = match ($status) {
        'near_limit' => 'warning',
        'at_limit', 'over_limit' => 'danger',
        default => 'azure',
    };

    $statusLabel = str_replace('_', ' ', ucfirst($status));
@endphp

<div class="alert alert-{{ $tone }} mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <div class="fw-semibold">{{ $title }}</div>
            <div class="small mt-1">
                {{ number_format($usage) }}
                / {{ $limit === null ? 'Unlimited' : number_format((int) $limit) }}
                @if($remaining !== null)
                    · Sisa {{ number_format((int) $remaining) }}
                @endif
            </div>
            @if($message)
                <div class="small text-muted mt-1">{{ $message }}</div>
            @endif
        </div>
        @if($showBadge)
            <span class="badge bg-{{ $tone }}-lt text-{{ $tone }}">
                {{ $statusLabel }}
            </span>
        @endif
    </div>
</div>
