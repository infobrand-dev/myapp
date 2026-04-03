@extends('layouts.admin')

@section('content')
@php
    $platformRegistry = app(\App\Modules\SocialMedia\Services\SocialPlatformRegistry::class);
    $platforms = collect($platformRegistry->summary());
    $livePlatforms = $platforms->where('public_enabled', true)->values();
    $upcomingPlatforms = $platforms->where('public_enabled', false)->values();
@endphp
<div class="page-header mb-3">
    <div class="row align-items-center w-100">
        <div class="col">
            <h2 class="mb-0">Instagram / Facebook DM Inbox</h2>
            <div class="text-muted small">Instagram Business DM dan Facebook Messenger yang sudah live.</div>
        </div>
        <div class="col-auto">
            <a href="{{ route('social-media.accounts.index') }}" class="btn btn-outline-secondary">Manage Accounts</a>
        </div>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-2">Live Channels</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($livePlatforms as $platform)
                        <span class="badge bg-success-lt text-success">{{ $platform['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-2">Internal Roadmap</div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach($upcomingPlatforms as $platform)
                        <span class="badge bg-secondary-lt text-secondary">{{ $platform['label'] }} ({{ $platform['status'] }})</span>
                    @endforeach
                </div>
                <div class="small text-muted">Belum dibuka ke tenant sampai connector, inbound, dan outbound benar-benar siap.</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Platform</th>
                    <th>Status</th>
                    <th>Agent</th>
                    <th>Terakhir Pesan</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conversation)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $conversation->contact_name ?: 'Unknown' }}</div>
                            <div class="text-muted small">{{ $conversation->contact_external_id }}</div>
                        </td>
                        <td>{{ ucfirst((string) ($conversation->metadata['platform'] ?? '-')) }}</td>
                        <td>
                            @if($conversation->status === 'open')
                                <span class="badge bg-success-lt">Open</span>
                            @else
                                <span class="badge bg-secondary-lt">{{ ucfirst($conversation->status) }}</span>
                            @endif
                            @if(!empty($conversation->metadata['auto_reply_paused']))
                                <span class="badge bg-warning-lt ms-1">Bot Paused</span>
                            @endif
                        </td>
                        <td>{{ $conversation->owner?->name ?? '-' }}</td>
                        <td>{{ $conversation->last_message_at?->diffForHumans() ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('social-media.conversations.show', $conversation) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada percakapan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $conversations->links() }}</div>
</div>
@endsection
