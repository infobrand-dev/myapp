@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Inbox WhatsApp API</h2>
        <div class="text-muted small">Claim percakapan eksklusif (auto-timeout {{ $lockMinutes }} menit). Setiap chat selalu terikat pada instance asal.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('whatsapp-api.inbox') }}" class="btn btn-outline-secondary">Refresh</a>
        @role('Super-admin')
        <a href="{{ route('whatsapp-api.logs.index') }}" class="btn btn-outline-secondary">Logs</a>
        <a href="{{ route('whatsapp-api.instances.index') }}" class="btn btn-outline-primary">Kelola Instance</a>
        @endrole
    </div>
</div>

@php
    $myEffectivePresence = $myPresence->effectiveStatus();
    $myManualPresence = $myPresence->manual_status ?: 'auto';
@endphp
<div class="card mb-3" data-user-presence data-heartbeat-url="{{ route('presence.heartbeat') }}" data-status-url="{{ route('presence.status') }}">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Status Saya</label>
                <div class="d-flex gap-2 align-items-center">
                    <select class="form-select" data-user-presence-select>
                        <option value="auto" {{ $myManualPresence === 'auto' ? 'selected' : '' }}>Auto</option>
                        <option value="online" {{ $myManualPresence === 'online' ? 'selected' : '' }}>Online</option>
                        <option value="away" {{ $myManualPresence === 'away' ? 'selected' : '' }}>Away</option>
                        <option value="busy" {{ $myManualPresence === 'busy' ? 'selected' : '' }}>Busy</option>
                        <option value="offline" {{ $myManualPresence === 'offline' ? 'selected' : '' }}>Offline</option>
                    </select>
                    <span data-user-presence-badge class="badge {{ match($myEffectivePresence) { 'online' => 'bg-green-lt text-green', 'away' => 'bg-yellow-lt text-yellow', 'busy' => 'bg-red-lt text-red', default => 'bg-secondary-lt text-secondary' } }}">
                        {{ ucfirst($myEffectivePresence) }}
                    </span>
                </div>
                <div class="text-muted small mt-1">Mode `Auto` memakai heartbeat browser. Auto-assignment memprioritaskan `online`, lalu `away`.</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Filter Instance</label>
                <select name="instance_id" class="form-select">
                    <option value="">Semua instance</option>
                    @foreach($instances as $inst)
                        <option value="{{ $inst->id }}" {{ (string) ($selectedInstanceId ?? '') === (string) $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Cari Kontak</label>
                <input type="text" name="q" class="form-control" placeholder="Nama kontak / nomor WA" value="{{ $search ?? '' }}">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Assignment</label>
                <select name="assignment" class="form-select">
                    <option value="">Semua</option>
                    <option value="mine" {{ ($assignment ?? '') === 'mine' ? 'selected' : '' }}>Milik Saya</option>
                    <option value="unassigned" {{ ($assignment ?? '') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                    <option value="assigned" {{ ($assignment ?? '') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="bot_paused" {{ ($assignment ?? '') === 'bot_paused' ? 'selected' : '' }}>Bot Paused</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Owner Presence</label>
                <select name="presence" class="form-select">
                    <option value="">Semua</option>
                    <option value="online" {{ ($presence ?? '') === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="away" {{ ($presence ?? '') === 'away' ? 'selected' : '' }}>Away</option>
                    <option value="busy" {{ ($presence ?? '') === 'busy' ? 'selected' : '' }}>Busy</option>
                    <option value="offline" {{ ($presence ?? '') === 'offline' ? 'selected' : '' }}>Offline</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a href="{{ route('whatsapp-api.inbox') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="d-none d-md-block card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Kontak</th>
                    <th>Instance</th>
                    <th>Status</th>
                    <th>Owner</th>
                    <th>Last Message</th>
                    <th class="w-1">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conv)
                    @php
                        $isOwner = (int) $conv->owner_id === (int) auth()->id();
                        $isLockedByOther = $conv->owner_id && !$isOwner && optional($conv->locked_until)->isFuture();
                        $metadata = is_array($conv->metadata) ? $conv->metadata : [];
                        $isBotPaused = (bool) ($metadata['auto_reply_paused'] ?? false);
                        $isAutoAssigned = (bool) ($metadata['auto_assigned'] ?? false);
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $conv->contact_name ?? $conv->contact_external_id }}</div>
                            <div class="text-muted small">{{ $conv->contact_external_id }}</div>
                        </td>
                        <td>
                            <span class="badge bg-azure-lt text-azure">{{ $conv->instance?->name ?? 'Instance missing' }}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge {{ $conv->status === 'closed' ? 'text-bg-secondary' : 'text-bg-primary' }}">{{ ucfirst($conv->status) }}</span>
                                @if($isAutoAssigned)
                                    <span class="badge bg-lime-lt text-lime">Auto Assigned</span>
                                @endif
                                @if($isBotPaused)
                                    <span class="badge bg-yellow-lt text-yellow">Bot Paused</span>
                                @endif
                                @if($isLockedByOther)
                                    <span class="badge bg-orange-lt text-orange">Locked {{ optional($conv->locked_until)->format('H:i') }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($conv->owner)
                                @php $ownerPresence = $ownerPresenceMap[$conv->owner->id] ?? 'offline'; @endphp
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-indigo-lt text-indigo">{{ $conv->owner->name }}</span>
                                    <span class="badge {{ match($ownerPresence) { 'online' => 'bg-green-lt text-green', 'away' => 'bg-yellow-lt text-yellow', 'busy' => 'bg-red-lt text-red', default => 'bg-secondary-lt text-secondary' } }}">{{ ucfirst($ownerPresence) }}</span>
                                </div>
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </td>
                        <td><span class="text-muted">{{ optional($conv->last_message_at)->diffForHumans() ?? '-' }}</span></td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                <a href="{{ route('conversations.show', $conv) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Open" aria-label="Open">
                                    <i class="ti ti-message-circle icon" aria-hidden="true"></i>
                                </a>

                                @if($isOwner)
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.conversations.release', $conv) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary btn-icon" type="submit" title="Release" aria-label="Release">
                                            <i class="ti ti-lock-open icon" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-azure btn-icon" data-bs-toggle="modal" data-bs-target="#inviteModal{{ $conv->id }}" title="Invite" aria-label="Invite">
                                        <i class="ti ti-user-plus icon" aria-hidden="true"></i>
                                    </button>
                                @elseif(!$conv->owner_id || optional($conv->locked_until)->isPast())
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.conversations.claim', $conv) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success btn-icon" type="submit" title="Claim" aria-label="Claim">
                                            <i class="ti ti-lock icon" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" disabled title="Locked" aria-label="Locked">
                                        <i class="ti ti-lock icon" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>

                            @if($isOwner)
                                <div class="modal fade" id="inviteModal{{ $conv->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content text-start">
                                            <form method="POST" action="{{ route('whatsapp-api.conversations.invite', $conv) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h3 class="modal-title">Invite Collaborator</h3>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">User</label>
                                                        <select name="user_id" class="form-select" required>
                                                            <option value="">Pilih user</option>
                                                            @foreach((optional($conv->instance)->users ?? collect()) as $invitee)
                                                                @if((int) $invitee->id !== (int) auth()->id())
                                                                    <option value="{{ $invitee->id }}">{{ $invitee->name }} (ID: {{ $invitee->id }})</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Role</label>
                                                        <select name="role" class="form-select">
                                                            <option value="collaborator">Collaborator</option>
                                                            <option value="viewer">Viewer</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Invite</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada percakapan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-md-none d-grid gap-2">
    @forelse($conversations as $conv)
        @php
            $isOwner = (int) $conv->owner_id === (int) auth()->id();
            $isLockedByOther = $conv->owner_id && !$isOwner && optional($conv->locked_until)->isFuture();
            $metadata = is_array($conv->metadata) ? $conv->metadata : [];
            $isBotPaused = (bool) ($metadata['auto_reply_paused'] ?? false);
            $isAutoAssigned = (bool) ($metadata['auto_assigned'] ?? false);
        @endphp
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-bold">{{ $conv->contact_name ?? $conv->contact_external_id }}</div>
                        <div class="text-muted small">{{ $conv->contact_external_id }}</div>
                    </div>
                    <span class="badge bg-azure-lt text-azure">{{ $conv->instance?->name ?? 'Instance missing' }}</span>
                </div>

                <div class="d-flex flex-wrap gap-1 mb-2">
                    <span class="badge {{ $conv->status === 'closed' ? 'text-bg-secondary' : 'text-bg-primary' }}">{{ ucfirst($conv->status) }}</span>
                    @if($isAutoAssigned)
                        <span class="badge bg-lime-lt text-lime">Auto Assigned</span>
                    @endif
                    @if($isBotPaused)
                        <span class="badge bg-yellow-lt text-yellow">Bot Paused</span>
                    @endif
                    @if($isLockedByOther)
                        <span class="badge bg-orange-lt text-orange">Locked {{ optional($conv->locked_until)->format('H:i') }}</span>
                    @endif
                </div>

                <div class="text-muted small mb-3">
                    @php $ownerPresence = $conv->owner ? ($ownerPresenceMap[$conv->owner->id] ?? 'offline') : null; @endphp
                    Owner: {{ $conv->owner?->name ?? 'Unassigned' }}{{ $ownerPresence ? ' (' . ucfirst($ownerPresence) . ')' : '' }} | Last: {{ optional($conv->last_message_at)->diffForHumans() ?? '-' }}
                </div>

                <div class="table-actions">
                    <a href="{{ route('conversations.show', $conv) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Open" aria-label="Open">
                        <i class="ti ti-message-circle icon" aria-hidden="true"></i>
                    </a>

                    @if($isOwner)
                        <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.conversations.release', $conv) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary btn-icon" type="submit" title="Release" aria-label="Release">
                                <i class="ti ti-lock-open icon" aria-hidden="true"></i>
                            </button>
                        </form>
                    @elseif(!$conv->owner_id || optional($conv->locked_until)->isPast())
                        <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.conversations.claim', $conv) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-success btn-icon" type="submit" title="Claim" aria-label="Claim">
                                <i class="ti ti-lock icon" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-muted">Belum ada percakapan.</div></div>
    @endforelse
</div>

<div class="mt-3">{{ $conversations->links() }}</div>
@endsection

@push('scripts')
    @include('shared.user-presence-heartbeat')
@endpush


