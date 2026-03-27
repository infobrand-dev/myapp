@extends('layouts.admin')

@php($hooks = app(\App\Support\HookManager::class))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Conversations</h2>
        <div class="text-muted small">Inbox kerja operator lintas channel. Claim eksklusif, release, dan queue prioritas.</div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Open Queue</div><div class="h2 mb-0">{{ $summary['total'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Unread</div><div class="h2 mb-0 text-warning">{{ $summary['unread'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Unassigned</div><div class="h2 mb-0">{{ $summary['unassigned'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Owned By Me</div><div class="h2 mb-0 text-primary">{{ $summary['mine'] ?? 0 }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kontak, nomor, pesan terakhir">
            </div>
            <div class="col-md-2">
                <label class="form-label">Channel</label>
                <select name="channel" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['internal' => 'Internal', 'wa_api' => 'WhatsApp API', 'wa_web' => 'WhatsApp Web', 'live_chat' => 'Live Chat', 'social' => 'Social'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['channel'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                    <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Assignment</label>
                <select name="assignment" class="form-select">
                    <option value="">Semua</option>
                    <option value="mine" @selected(($filters['assignment'] ?? '') === 'mine')>Milik saya</option>
                    <option value="unassigned" @selected(($filters['assignment'] ?? '') === 'unassigned')>Belum di-claim</option>
                    <option value="others" @selected(($filters['assignment'] ?? '') === 'others')>Milik orang lain</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Unread Only</label>
                <div class="form-check form-switch pt-2">
                    <input class="form-check-input" type="checkbox" name="unread_only" value="1" @checked($filters['unread_only'] ?? false)>
                    <label class="form-check-label">Tampilkan unread saja</label>
                </div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a href="{{ route('conversations.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Kontak/Channel</th>
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
                    <tr>
                        <td>
                            <a href="{{ route('conversations.show', $conv) }}" class="fw-bold text-decoration-none">{{ $conv->contact_name ?? $conv->contact_external_id ?? 'Internal Chat' }}</a>
                            <div class="text-muted small">{{ strtoupper($conv->channel ?? 'internal') }}</div>
                            @if($conv->latestMessage)
                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit((string) $conv->latestMessage->body, 60) }}</div>
                            @endif
                        </td>
                        <td><span class="badge {{ $conv->status === 'closed' ? 'text-bg-secondary' : 'text-bg-primary' }}">{{ ucfirst($conv->status) }}</span></td>
                        <td>
                            @if((int) ($conv->unread_count ?? 0) > 0)
                                <span class="badge bg-warning-lt text-warning">{{ (int) $conv->unread_count }}</span>
                            @else
                                <span class="text-muted small">0</span>
                            @endif
                        </td>
                        <td>{{ $conv->owner->name ?? 'Unassigned' }}</td>
                        <td>
                            @if($conv->locked_until && $conv->locked_until->isFuture())
                                <span class="badge text-bg-secondary">until {{ $conv->locked_until->format('H:i') }}</span>
                            @elseif($conv->owner_id)
                                <span class="badge bg-secondary-lt text-secondary">owned</span>
                            @else
                                <span class="text-muted small">free</span>
                            @endif
                        </td>
                        <td>
                            @php($integrationBadges = $hooks->render('conversations.index.integration_badges', ['conversation' => $conv]))
                            @if(!empty($integrationBadges))
                                {!! implode('', $integrationBadges) !!}
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>{{ optional($conv->last_message_at)->diffForHumans() ?? '-' }}</td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                <a href="{{ route('conversations.show', $conv) }}" class="btn btn-outline-secondary btn-sm">Open</a>
                                @if($conv->owner_id === auth()->id())
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('conversations.release', $conv) }}">
                                        @csrf
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">Release</button>
                                    </form>
                                @elseif(!$conv->owner_id || optional($conv->locked_until)->isPast())
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('conversations.claim', $conv) }}">
                                        @csrf
                                        <button class="btn btn-primary btn-sm" type="submit">Claim</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Locked sampai {{ optional($conv->locked_until)->format('H:i') }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">Belum ada percakapan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $conversations->links() }}</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title mb-0">Mulai Percakapan Internal</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('conversations.start') }}" class="d-flex gap-2" onsubmit="return confirm('Mulai chat dengan ' + document.getElementById('start-query').value + '?')">
            @csrf
            <input type="text" name="query" id="start-query" class="form-control" placeholder="Nama atau Email" required>
            <button class="btn btn-primary" type="submit">Start</button>
        </form>
        <div class="text-muted small mt-2">Masukkan user ID rekan untuk membuat percakapan baru.</div>
    </div>
</div>
@endsection

