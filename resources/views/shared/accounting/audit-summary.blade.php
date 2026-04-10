@php
    $entries = collect($entries ?? [])->filter(fn ($entry) => !empty($entry['user']) || !empty($entry['timestamp']));
@endphp

<div class="card {{ $cardClass ?? '' }}">
    <div class="card-header">
        <h3 class="card-title">Audit</h3>
        <div class="card-options">
            @include('shared.accounting.mode-badge')
        </div>
    </div>
    <div class="card-body d-flex flex-column gap-3">
        @forelse($entries as $entry)
            @php
                $color = $entry['color'] ?? 'secondary';
                $icon = $entry['icon'] ?? 'ti-user';
                $label = $entry['label'] ?? 'Updated by';
                $user = $entry['user'] ?? null;
                $timestamp = $entry['timestamp'] ?? null;
            @endphp
            <div class="d-flex align-items-start gap-3">
                <span class="avatar avatar-sm bg-{{ $color }}-lt flex-shrink-0">
                    <i class="ti {{ $icon }}" style="font-size:.9rem; color:var(--tblr-{{ $color }});"></i>
                </span>
                <div>
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fw-medium">{{ $user?->name ?? '-' }}</div>
                    @if($timestamp)
                        <div class="text-muted small">{{ $timestamp->format('d M Y, H:i') }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-muted">Belum ada audit ringkas.</div>
        @endforelse
    </div>
</div>
