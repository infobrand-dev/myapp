@extends('layouts.tenant')

@section('title', 'Inbox WhatsApp API')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">WhatsApp API</div>
            <h2 class="page-title">Inbox</h2>
            <p class="text-muted mb-0">Claim percakapan eksklusif (auto-timeout {{ $lockMinutes }} menit). Setiap chat selalu terikat pada instance asal.</p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('whatsapp-api.inbox') }}" class="btn btn-outline-secondary">
                <i class="ti ti-refresh me-1"></i>Refresh
            </a>
            @role('Super-admin')
            <a href="{{ route('whatsapp-api.logs.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-list me-1"></i>Logs
            </a>
            <a href="{{ route('whatsapp-api.instances.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-settings me-1"></i>Kelola Instance
            </a>
            @endrole
        </div>
    </div>
</div>

{{-- Presence Status --}}
@php
    $myEffectivePresence = $myPresence->effectiveStatus();
    $myManualPresence = $myPresence->manual_status ?: 'auto';
    $presenceBadgeClass = match($myEffectivePresence) {
        'online' => 'bg-green-lt text-green',
        'away'   => 'bg-orange-lt text-orange',
        'busy'   => 'bg-red-lt text-red',
        default  => 'bg-secondary-lt text-secondary',
    };
@endphp
<div class="card mb-3" data-user-presence data-heartbeat-url="{{ route('presence.heartbeat') }}" data-status-url="{{ route('presence.status') }}">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Status Saya</label>
                <div class="d-flex gap-2 align-items-center">
                    <select class="form-select" data-user-presence-select>
                        <option value="auto"    {{ $myManualPresence === 'auto'    ? 'selected' : '' }}>Auto</option>
                        <option value="online"  {{ $myManualPresence === 'online'  ? 'selected' : '' }}>Online</option>
                        <option value="away"    {{ $myManualPresence === 'away'    ? 'selected' : '' }}>Away</option>
                        <option value="busy"    {{ $myManualPresence === 'busy'    ? 'selected' : '' }}>Busy</option>
                        <option value="offline" {{ $myManualPresence === 'offline' ? 'selected' : '' }}>Offline</option>
                    </select>
                    <span data-user-presence-badge class="badge {{ $presenceBadgeClass }}">
                        {{ ucfirst($myEffectivePresence) }}
                    </span>
                </div>
                <div class="form-hint">Mode <code>Auto</code> memakai heartbeat browser. Auto-assignment prioritaskan <code>online</code>, lalu <code>away</code>.</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="inbox-filter-form" class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Filter Instance</label>
                <select name="instance_id" class="form-select" data-auto-submit>
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
                <select name="assignment" class="form-select" data-auto-submit>
                    <option value="">Semua</option>
                    <option value="mine"       {{ ($assignment ?? '') === 'mine'       ? 'selected' : '' }}>Milik Saya</option>
                    <option value="unassigned" {{ ($assignment ?? '') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                    <option value="assigned"   {{ ($assignment ?? '') === 'assigned'   ? 'selected' : '' }}>Assigned</option>
                    <option value="bot_paused" {{ ($assignment ?? '') === 'bot_paused' ? 'selected' : '' }}>Bot Paused</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Owner Presence</label>
                <select name="presence" class="form-select" data-auto-submit>
                    <option value="">Semua</option>
                    <option value="online"  {{ ($presence ?? '') === 'online'  ? 'selected' : '' }}>Online</option>
                    <option value="away"    {{ ($presence ?? '') === 'away'    ? 'selected' : '' }}>Away</option>
                    <option value="busy"    {{ ($presence ?? '') === 'busy'    ? 'selected' : '' }}>Busy</option>
                    <option value="offline" {{ ($presence ?? '') === 'offline' ? 'selected' : '' }}>Offline</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">
                    <i class="ti ti-filter me-1"></i>Apply
                </button>
                <a href="{{ route('whatsapp-api.inbox') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Desktop table --}}
<div class="d-none d-md-block card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Kontak</th>
                        <th>Instance</th>
                        <th>Status</th>
                        <th>Owner</th>
                        <th>Last Message</th>
                        <th class="w-1"></th>
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
                                <div class="fw-semibold">{{ $conv->contact_name ?? $conv->contact_external_id }}</div>
                                <div class="text-muted small">{{ $conv->contact_external_id }}</div>
                            </td>
                            <td>
                                <span class="badge bg-azure-lt text-azure">{{ $conv->instance?->name ?? 'Instance missing' }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if($conv->status === 'closed')
                                        <span class="badge bg-secondary-lt text-secondary">Closed</span>
                                    @else
                                        <span class="badge bg-green-lt text-green">Open</span>
                                    @endif
                                    @if($isAutoAssigned)
                                        <span class="badge bg-blue-lt text-blue">Auto Assigned</span>
                                    @endif
                                    @if($isBotPaused)
                                        <span class="badge bg-orange-lt text-orange">Bot Paused</span>
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
                                        <span class="badge {{ match($ownerPresence) {
                                            'online' => 'bg-green-lt text-green',
                                            'away'   => 'bg-orange-lt text-orange',
                                            'busy'   => 'bg-red-lt text-red',
                                            default  => 'bg-secondary-lt text-secondary',
                                        } }}">{{ ucfirst($ownerPresence) }}</span>
                                    </div>
                                @else
                                    <span class="text-muted small">Unassigned</span>
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

                                    @if($isOwner)
                                        <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.conversations.release', $conv) }}">
                                            @csrf
                                            <button class="btn btn-icon btn-sm btn-outline-secondary" type="submit" title="Release">
                                                <i class="ti ti-lock-open"></i>
                                            </button>
                                        </form>
                                        <button type="button"
                                                class="btn btn-icon btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#inviteModal{{ $conv->id }}"
                                                title="Invite Collaborator">
                                            <i class="ti ti-user-plus"></i>
                                        </button>
                                    @elseif(!$conv->owner_id || optional($conv->locked_until)->isPast())
                                        <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.conversations.claim', $conv) }}">
                                            @csrf
                                            <button class="btn btn-icon btn-sm btn-outline-primary" type="submit" title="Claim">
                                                <i class="ti ti-lock"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-icon btn-sm btn-outline-secondary" type="button" disabled title="Locked">
                                            <i class="ti ti-lock"></i>
                                        </button>
                                    @endif
                                </div>

                                @if($isOwner)
                                    <div class="modal fade" id="inviteModal{{ $conv->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-start">
                                                <form method="POST" action="{{ route('whatsapp-api.conversations.invite', $conv) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h3 class="modal-title">Invite Collaborator</h3>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">User <span class="text-danger">*</span></label>
                                                            <select name="user_id" class="form-select" required>
                                                                <option value="">Pilih user</option>
                                                                @foreach((optional($conv->instance)->users ?? collect()) as $invitee)
                                                                    @if((int) $invitee->id !== (int) auth()->id())
                                                                        <option value="{{ $invitee->id }}">{{ $invitee->name }}</option>
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
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="ti ti-user-plus me-1"></i>Invite
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-messages text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted">Belum ada percakapan.</div>
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

{{-- Mobile card list --}}
<div class="d-md-none">
    @forelse($conversations as $conv)
        @php
            $isOwner = (int) $conv->owner_id === (int) auth()->id();
            $isLockedByOther = $conv->owner_id && !$isOwner && optional($conv->locked_until)->isFuture();
            $metadata = is_array($conv->metadata) ? $conv->metadata : [];
            $isBotPaused = (bool) ($metadata['auto_reply_paused'] ?? false);
            $isAutoAssigned = (bool) ($metadata['auto_assigned'] ?? false);
        @endphp
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-semibold">{{ $conv->contact_name ?? $conv->contact_external_id }}</div>
                        <div class="text-muted small">{{ $conv->contact_external_id }}</div>
                    </div>
                    <span class="badge bg-azure-lt text-azure">{{ $conv->instance?->name ?? '—' }}</span>
                </div>

                <div class="d-flex flex-wrap gap-1 mb-2">
                    @if($conv->status === 'closed')
                        <span class="badge bg-secondary-lt text-secondary">Closed</span>
                    @else
                        <span class="badge bg-green-lt text-green">Open</span>
                    @endif
                    @if($isAutoAssigned)
                        <span class="badge bg-blue-lt text-blue">Auto Assigned</span>
                    @endif
                    @if($isBotPaused)
                        <span class="badge bg-orange-lt text-orange">Bot Paused</span>
                    @endif
                    @if($isLockedByOther)
                        <span class="badge bg-orange-lt text-orange">Locked</span>
                    @endif
                </div>

                @php $ownerPresence = $conv->owner ? ($ownerPresenceMap[$conv->owner->id] ?? 'offline') : null; @endphp
                <div class="text-muted small mb-3">
                    Owner: {{ $conv->owner?->name ?? 'Unassigned' }}{{ $ownerPresence ? ' · ' . ucfirst($ownerPresence) : '' }}
                    · Last: {{ optional($conv->last_message_at)->diffForHumans() ?? '—' }}
                </div>

                <div class="table-actions">
                    <a href="{{ route('conversations.show', $conv) }}"
                       class="btn btn-icon btn-sm btn-outline-secondary"
                       title="Buka">
                        <i class="ti ti-message-circle"></i>
                    </a>
                    @if($isOwner)
                        <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.conversations.release', $conv) }}">
                            @csrf
                            <button class="btn btn-icon btn-sm btn-outline-secondary" type="submit" title="Release">
                                <i class="ti ti-lock-open"></i>
                            </button>
                        </form>
                    @elseif(!$conv->owner_id || optional($conv->locked_until)->isPast())
                        <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.conversations.claim', $conv) }}">
                            @csrf
                            <button class="btn btn-icon btn-sm btn-outline-primary" type="submit" title="Claim">
                                <i class="ti ti-lock"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ti ti-messages text-muted d-block mb-2" style="font-size:2rem;"></i>
                <div class="text-muted">Belum ada percakapan.</div>
            </div>
        </div>
    @endforelse
    <div class="mt-2">{{ $conversations->links() }}</div>
</div>

@endsection

@push('scripts')
    @include('shared.user-presence-heartbeat')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
            el.addEventListener('change', function () {
                document.getElementById('inbox-filter-form').submit();
            });
        });
    });
    </script>
@endpush

