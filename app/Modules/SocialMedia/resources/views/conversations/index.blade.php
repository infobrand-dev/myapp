@extends('layouts.admin')

@section('title', 'Social Media Inbox')

@section('content')
@php
    $platformRegistry = app(\App\Modules\SocialMedia\Services\SocialPlatformRegistry::class);
    $platforms = collect($platformRegistry->summary());
    $livePlatforms = $platforms
        ->filter(fn ($p) => ($p['public_enabled'] ?? false) && ($p['supports_inbound_webhook'] ?? false) && ($p['supports_outbound_messages'] ?? false))
        ->values();
    $upcomingPlatforms = $platforms
        ->reject(fn ($p) => ($p['public_enabled'] ?? false) && ($p['supports_inbound_webhook'] ?? false) && ($p['supports_outbound_messages'] ?? false))
        ->values();
@endphp

{{-- ══ PAGE HEADER ══════════════════════════════════════════ --}}
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Social Media</div>
            <h2 class="page-title">Inbox Percakapan</h2>
            <p class="text-muted mb-0">Instagram, Facebook DM, dan channel sosial lainnya yang sudah terhubung.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('social-media.accounts.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-plug me-1"></i>Kelola Akun
            </a>
        </div>
    </div>
</div>

{{-- ══ CHANNEL STATUS ═══════════════════════════════════════ --}}
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;">Live Channels</div>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($livePlatforms as $platform)
                        <span class="badge bg-green-lt text-green">
                            <i class="ti ti-circle-filled me-1" style="font-size:.55rem;"></i>{{ $platform['label'] }}
                        </span>
                    @empty
                        <span class="text-muted small">Belum ada channel yang aktif.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;">Segera Hadir</div>
                <div class="d-flex flex-wrap gap-2 mb-1">
                    @foreach($upcomingPlatforms as $platform)
                        <span class="badge bg-secondary-lt text-secondary">{{ $platform['label'] }} ({{ $platform['status'] }})</span>
                    @endforeach
                </div>
                <div class="small text-muted">Belum dibuka sampai connector, inbound, dan outbound siap.</div>
            </div>
        </div>
    </div>
</div>

{{-- ══ CONVERSATIONS TABLE ══════════════════════════════════ --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Kontak</th>
                        <th>Platform</th>
                        <th>Status</th>
                        <th>Agent</th>
                        <th>Terakhir Pesan</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversations as $conversation)
                        @php
                            $pKey  = strtolower((string) ($conversation->metadata['platform'] ?? ''));
                            $pLabel = ucfirst($pKey ?: '-');
                            $pIcons = ['instagram' => 'ti-brand-instagram', 'facebook' => 'ti-brand-facebook', 'messenger' => 'ti-brand-facebook', 'tiktok' => 'ti-brand-tiktok', 'twitter' => 'ti-brand-twitter', 'x' => 'ti-brand-x'];
                            $pIcon  = $pIcons[$pKey] ?? 'ti-messages';
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $conversation->contact_name ?: 'Unknown' }}</div>
                                <div class="text-muted small">{{ $conversation->contact_external_id }}</div>
                            </td>
                            <td>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="ti {{ $pIcon }} text-muted"></i>
                                    {{ $pLabel }}
                                </span>
                            </td>
                            <td>
                                @if($conversation->status === 'open')
                                    <span class="badge bg-green-lt text-green">Open</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">{{ ucfirst($conversation->status) }}</span>
                                @endif
                                @if(!empty($conversation->metadata['auto_reply_paused']))
                                    <span class="badge bg-orange-lt text-orange ms-1">Bot Paused</span>
                                @endif
                            </td>
                            <td>{{ $conversation->owner?->name ?? '-' }}</td>
                            <td>{{ $conversation->last_message_at?->diffForHumans() ?? '-' }}</td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('social-media.conversations.show', $conversation) }}"
                                        class="btn btn-icon btn-sm btn-outline-primary" title="Buka Percakapan">
                                        <i class="ti ti-message-circle"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-messages text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada percakapan masuk.</div>
                                <a href="{{ route('social-media.accounts.index') }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-plug me-1"></i>Hubungkan Akun
                                </a>
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

@endsection
