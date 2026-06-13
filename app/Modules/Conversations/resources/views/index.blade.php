@extends('layouts.tenant')

@section('title', 'Conversations')

@php
    $hooks = app(\App\Support\HookManager::class);
@endphp

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Komunikasi</div>
            <h2 class="page-title">Conversations</h2>
            <p class="text-muted mb-0">Inbox kerja operator lintas channel. Claim eksklusif, release, dan queue prioritas.</p>
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Open Queue</div>
                    <i class="ti ti-inbox" style="font-size:1.3rem; color:var(--tblr-blue);"></i>
                </div>
                <div class="fs-1 fw-bold">{{ $summary['total'] ?? 0 }}</div>
                <div class="text-muted small mt-1">Total percakapan aktif</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Unread</div>
                    <i class="ti ti-message-2-exclamation" style="font-size:1.3rem; color:var(--tblr-orange);"></i>
                </div>
                <div class="fs-1 fw-bold">{{ $summary['unread'] ?? 0 }}</div>
                <div class="text-muted small mt-1">Pesan belum dibaca</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Unassigned</div>
                    <i class="ti ti-user-question" style="font-size:1.3rem; color:var(--tblr-red);"></i>
                </div>
                <div class="fs-1 fw-bold">{{ $summary['unassigned'] ?? 0 }}</div>
                <div class="text-muted small mt-1">Belum ada owner</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Owned By Me</div>
                    <i class="ti ti-user-check" style="font-size:1.3rem; color:var(--tblr-green);"></i>
                </div>
                <div class="fs-1 fw-bold">{{ $summary['mine'] ?? 0 }}</div>
                <div class="text-muted small mt-1">Percakapan milik Anda</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="filter-form" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Cari</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kontak, nomor, pesan terakhir">
            </div>
            <div class="col-md-2">
                <label class="form-label">Channel</label>
                <select name="channel" class="form-select" data-auto-submit>
                    <option value="">Semua</option>
                    @foreach(['internal' => 'Internal', 'wa_api' => 'WhatsApp API', 'wa_web' => 'WhatsApp Web', 'live_chat' => 'Live Chat', 'social' => 'Social'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['channel'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" data-auto-submit>
                    <option value="">Semua</option>
                    <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                    <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Assignment</label>
                <select name="assignment" class="form-select" data-auto-submit>
                    <option value="">Semua</option>
                    <option value="mine" @selected(($filters['assignment'] ?? '') === 'mine')>Milik saya</option>
                    <option value="unassigned" @selected(($filters['assignment'] ?? '') === 'unassigned')>Belum di-claim</option>
                    <option value="others" @selected(($filters['assignment'] ?? '') === 'others')>Milik orang lain</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button class="btn btn-primary" type="submit">
                        <i class="ti ti-filter me-1"></i>Filter
                    </button>
                    <a href="{{ route('conversations.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="unread_only" value="1" id="unread-only-check" @checked($filters['unread_only'] ?? false)>
                    <label class="form-check-label" for="unread-only-check">Tampilkan unread saja</label>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Kontak / Channel</th>
                        <th>Status</th>
                        <th>Unread</th>
                        <th>Owner</th>
                        <th>Lock</th>
                        <th>Instance</th>
                        <th>Last Msg</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversations as $conv)
                        @php
                            $channelLabel = match(strtolower($conv->channel ?? 'internal')) {
                                'wa_api' => 'WhatsApp API',
                                'wa_web' => 'WhatsApp Web',
                                'live_chat' => 'Live Chat',
                                'social', 'social_dm' => 'Social',
                                default => ucfirst($conv->channel ?? 'Internal'),
                            };
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('conversations.show', $conv) }}" class="fw-semibold text-decoration-none">
                                    {{ $conv->contact_name ?? $conv->contact_external_id ?? 'Internal Chat' }}
                                </a>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="badge bg-blue-lt text-blue">{{ $channelLabel }}</span>
                                </div>
                                @if($conv->latestMessage)
                                    <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit((string) $conv->latestMessage->body, 60) }}</div>
                                @endif
                            </td>
                            <td>
                                @if($conv->status === 'closed')
                                    <span class="badge bg-secondary-lt text-secondary">Closed</span>
                                @else
                                    <span class="badge bg-green-lt text-green">Open</span>
                                @endif
                            </td>
                            <td>
                                @if((int) ($conv->effective_unread_count ?? 0) > 0)
                                    <span class="badge bg-orange-lt text-orange fw-bold">{{ (int) $conv->effective_unread_count }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if($conv->owner)
                                    <span class="text-body">{{ $conv->owner->name }}</span>
                                @else
                                    <span class="text-muted small">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                @if($conv->locked_until && $conv->locked_until->isFuture())
                                    <span class="badge bg-orange-lt text-orange">sampai {{ $conv->locked_until->format('H:i') }}</span>
                                @elseif($conv->owner_id)
                                    <span class="badge bg-secondary-lt text-secondary">owned</span>
                                @else
                                    <span class="badge bg-green-lt text-green">free</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $integrationBadges = $hooks->render('conversations.index.integration_badges', ['conversation' => $conv]);
                                @endphp
                                @if(!empty($integrationBadges))
                                    {!! implode('', $integrationBadges) !!}
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ optional($conv->last_message_at)->diffForHumans() ?? '—' }}</td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('conversations.show', $conv) }}"
                                       class="btn btn-icon btn-sm btn-outline-secondary"
                                       title="Buka Percakapan">
                                        <i class="ti ti-message-circle"></i>
                                    </a>
                                    @if($conv->owner_id === auth()->id())
                                        <form class="d-inline-block m-0" method="POST" action="{{ route('conversations.release', $conv) }}">
                                            @csrf
                                            <button class="btn btn-icon btn-sm btn-outline-secondary"
                                                    type="submit"
                                                    title="Release"
                                                    data-confirm="Release percakapan ini?">
                                                <i class="ti ti-lock-open"></i>
                                            </button>
                                        </form>
                                    @elseif(!$conv->owner_id || optional($conv->locked_until)->isPast())
                                        <form class="d-inline-block m-0" method="POST" action="{{ route('conversations.claim', $conv) }}">
                                            @csrf
                                            <button class="btn btn-icon btn-sm btn-outline-primary"
                                                    type="submit"
                                                    title="Claim">
                                                <i class="ti ti-lock"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-icon btn-sm btn-outline-secondary" type="button" disabled title="Locked sampai {{ optional($conv->locked_until)->format('H:i') }}">
                                            <i class="ti ti-lock"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="ti ti-messages text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada percakapan.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $conversations->links() }}
    </div>
</div>

{{-- Start Internal Conversation --}}
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-user-plus me-2"></i>Mulai Percakapan Internal
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('conversations.start') }}" class="row g-2" id="start-internal-form">
            @csrf
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" name="query" id="start-query" class="form-control" placeholder="Nama atau Email rekan" required>
                    <button class="btn btn-primary" type="submit">
                        <i class="ti ti-send me-1"></i>Start
                    </button>
                </div>
                <div class="form-hint">Masukkan nama atau email rekan untuk membuat percakapan baru.</div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-submit filter on select change
    document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
        el.addEventListener('change', function () {
            document.getElementById('filter-form').submit();
        });
    });

    // Confirm before starting internal conversation
    const startForm = document.getElementById('start-internal-form');
    if (startForm) {
        startForm.addEventListener('submit', function (e) {
            const query = document.getElementById('start-query').value.trim();
            if (!query) return;
            if (!confirm('Mulai percakapan internal dengan "' + query + '"?')) {
                e.preventDefault();
            }
        });
    }
});
</script>
@endpush

