@extends('layouts.admin')

@section('content')
@php
    $integrationAutoReply = old('auto_reply', data_get($integration, 'auto_reply', false));
    $integrationChatbotAccountId = old('chatbot_account_id', data_get($integration, 'chatbot_account_id'));
    $chatbotEnabled = $chatbotEnabled ?? false;
    $metaOAuthReady = $metaOAuthReady ?? false;
    $internalCreateMode = $internalCreateMode ?? false;
    $isXPlatform = (($account->platform ?? old('platform')) === 'x');
    $isTikTokPlatform = (($account->platform ?? old('platform')) === 'tiktok');
    $xCreateMode = $xCreateMode ?? ($internalCreateMode ? 'internal' : 'edit');
    $metadata = is_array($account->metadata ?? null) ? $account->metadata : [];
@endphp
<div class="page-header mb-3">
    <div class="row align-items-center w-100">
        <div class="col">
            <h2 class="mb-0">Pengaturan Social Account</h2>
            <div class="text-muted small">
                @if($isXPlatform)
                    @if($xCreateMode === 'internal')
                        Konfigurasi internal connector X.
                    @else
                        Akun X terhubung melalui OAuth platform.
                    @endif
                @elseif($isTikTokPlatform)
                    Akun TikTok terhubung melalui OAuth platform untuk profile, stats, dan video list.
                @else
                    Kredensial dihubungkan melalui Meta OAuth platform. Tenant hanya mengatur status dan AI auto-reply.
                @endif
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('social-media.accounts.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>
</div>

@if(!$metaOAuthReady && !$isXPlatform && !$isTikTokPlatform)
    <div class="alert alert-warning">
        META OAuth belum siap di environment platform. Isi <code>META_APP_ID</code> dan <code>META_APP_SECRET</code>, lalu reconnect akun ini.
    </div>
@endif

@if($errors->has('connection_test'))
    <div class="alert alert-danger">{{ $errors->first('connection_test') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $internalCreateMode ? route('social-media.accounts.internal.x.store') : route('social-media.accounts.update', $account) }}">
            @csrf
            @unless($internalCreateMode)
                @method('PUT')
            @endunless
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Platform</label>
                    <input type="text" class="form-control" value="{{ ucfirst($account->platform) }}" readonly>
                </div>
                @unless($isXPlatform || $isTikTokPlatform)
                    <div class="col-md-4">
                        <label class="form-label">Page ID (FB)</label>
                        <input type="text" class="form-control" value="{{ $account->page_id }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">IG Business ID</label>
                        <input type="text" class="form-control" value="{{ $account->ig_business_id }}" readonly>
                    </div>
                @endunless

                @if(!$isXPlatform && !$isTikTokPlatform)
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            Token akses tidak diinput manual oleh tenant. Jika akses Meta berubah atau tenant ingin mengganti Page/Instagram yang terhubung, klik
                            <a href="{{ route('social-media.accounts.connect.meta') }}" class="alert-link">Hubungkan Meta</a>
                            untuk sinkron ulang akun dari platform OAuth.
                        </div>
                    </div>
                @else
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">
                            @if($xCreateMode === 'internal')
                                Konfigurasi ini khusus internal/admin. Jangan expose ke tenant umum sebelum connector X benar-benar siap.
                            @else
                                Koneksi X menggunakan OAuth. Jika token atau akses berubah, hubungkan ulang akun X dari halaman Social Accounts.
                            @endif
                        </div>
                    </div>
                @elseif($isTikTokPlatform)
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            Koneksi TikTok menggunakan OAuth. Data yang saat ini ditampilkan meliputi profil akun, statistik dasar, dan daftar video terbaru.
                        </div>
                    </div>
                @endif

                <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['active','inactive'] as $st)
                            <option value="{{ $st }}" {{ old('status', $account->status) === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>

                @unless($isTikTokPlatform)
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="auto_reply" value="1" id="auto_reply" {{ $integrationAutoReply ? 'checked' : '' }} {{ $chatbotEnabled ? '' : 'disabled' }}>
                            <label class="form-check-label" for="auto_reply">Auto-reply AI</label>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Chatbot Account</label>
                        <select name="chatbot_account_id" class="form-select" {{ $chatbotEnabled ? '' : 'disabled' }}>
                            <option value="">-- Pilih AI --</option>
                            @foreach(($chatbotAccounts ?? []) as $ai)
                                <option value="{{ $ai->id }}" {{ (string) $integrationChatbotAccountId === (string) $ai->id ? 'selected' : '' }}>{{ $ai->name }} ({{ $ai->model ?? 'default' }})</option>
                            @endforeach
                        </select>
                        <div class="text-muted small">
                            @if($chatbotEnabled)
                                Aktifkan auto-reply setelah akun channel siap digunakan.
                            @else
                                Install dan aktifkan module Chatbot untuk menghubungkan auto-reply AI.
                            @endif
                        </div>
                    </div>
                @else
                    <div class="col-12">
                        <div class="text-muted small">TikTok saat ini belum terhubung ke inbox/chatbot karena capability messaging belum disediakan pada fondasi ini.</div>
                    </div>
                @endunless

                @if($isXPlatform)
                    <div class="col-md-4">
                        <label class="form-label">X User ID</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'x_user_id') }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">X Handle</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'x_handle') }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Connector Status</label>
                        <select name="x_connector_status" class="form-select">
                            @foreach(['not_configured' => 'Not configured', 'configured' => 'Configured', 'active' => 'Active', 'error' => 'Error'] as $value => $label)
                                <option value="{{ $value }}" {{ old('x_connector_status', data_get($metadata, 'x_connector_status', 'not_configured')) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Connection Source</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'connection_source', '-') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">OAuth Connected</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'oauth_connected_at', '-') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">OAuth Refreshed</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'oauth_refreshed_at', '-') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Webhook Status</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'x_webhook_last_event_at') ? 'Active' : 'Waiting for first event' }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Webhook URL</label>
                        <input type="text" class="form-control" value="{{ route('social-media.webhook.x') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Connection Test</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'last_connection_tested_at', '-') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Connection Test Status</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'last_connection_test_status', '-') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Connection Test Result</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'last_connection_test_message', '-') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Token Refresh</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'last_token_refresh_attempt_at', '-') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Token Refresh Status</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'last_token_refresh_status', '-') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Token Refresh Result</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'last_token_refresh_message', '-') }}" readonly>
                    </div>
                    <div class="col-12">
                        <div class="card bg-light-lt border-0">
                            <div class="card-body py-3">
                                <div class="fw-semibold mb-2">Setup Webhook X</div>
                                <div class="text-muted small">1. Daftarkan callback URL berikut di app X Anda: <code>{{ route('social-media.webhook.x') }}</code></div>
                                <div class="text-muted small">2. Pakai webhook secret platform yang sama dengan <code>X_API_WEBHOOK_SECRET</code>.</div>
                                <div class="text-muted small">3. Pastikan akun X ini sudah memiliki DM access dan event webhook aktif.</div>
                                <div class="text-muted small">4. Media outbound X saat ini mendukung image, gif, dan video. File/document belum didukung provider.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <form method="POST" action="{{ route('social-media.accounts.test-connection', $account) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary">Test Connection</button>
                            </form>
                            <a href="{{ route('social-media.accounts.connect.x') }}" class="btn btn-outline-primary">Hubungkan Ulang X</a>
                        </div>
                    </div>
                @endif

                @if($isTikTokPlatform)
                    <div class="col-md-4">
                        <label class="form-label">TikTok Open ID</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'tiktok_open_id') }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'tiktok_username') }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Connection Source</label>
                        <input type="text" class="form-control" value="{{ data_get($metadata, 'connection_source', '-') }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Followers</label>
                        <input type="text" class="form-control" value="{{ number_format((int) data_get($metadata, 'tiktok_stats.followers', 0)) }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Following</label>
                        <input type="text" class="form-control" value="{{ number_format((int) data_get($metadata, 'tiktok_stats.following', 0)) }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Likes</label>
                        <input type="text" class="form-control" value="{{ number_format((int) data_get($metadata, 'tiktok_stats.likes', 0)) }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Videos</label>
                        <input type="text" class="form-control" value="{{ number_format((int) data_get($metadata, 'tiktok_stats.videos', 0)) }}" readonly>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('social-media.accounts.connect.tiktok') }}" class="btn btn-outline-primary">Hubungkan Ulang TikTok</a>
                            @if(data_get($metadata, 'tiktok_profile_url'))
                                <a href="{{ data_get($metadata, 'tiktok_profile_url') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Buka Profil TikTok</a>
                            @endif
                        </div>
                    </div>
                    @if(!empty(data_get($metadata, 'tiktok_videos', [])))
                        <div class="col-12">
                            <div class="card bg-light-lt border-0">
                                <div class="card-body">
                                    <div class="fw-semibold mb-3">Video Terbaru</div>
                                    <div class="row g-3">
                                        @foreach((array) data_get($metadata, 'tiktok_videos', []) as $video)
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 h-100 bg-white">
                                                    <div class="fw-semibold mb-1">{{ data_get($video, 'title') ?: ('Video ' . data_get($video, 'id')) }}</div>
                                                    <div class="text-muted small mb-2">{{ data_get($video, 'video_description') ?: '-' }}</div>
                                                    <div class="small text-muted">Views: {{ number_format((int) data_get($video, 'view_count', 0)) }} · Likes: {{ number_format((int) data_get($video, 'like_count', 0)) }}</div>
                                                    @if(data_get($video, 'share_url'))
                                                        <a href="{{ data_get($video, 'share_url') }}" target="_blank" rel="noopener" class="small d-inline-block mt-2">Buka video</a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <button class="btn btn-primary" type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

@unless($internalCreateMode)
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Health</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small">Inbound terakhir</div>
                <div>{{ optional($account->lastInboundAt())->diffForHumans() ?? '-' }}</div>
                @if($account->lastInboundSummary())
                    <div class="small text-body mt-1">"{{ $account->lastInboundSummary() }}"</div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Outbound terakhir</div>
                <div>{{ optional($account->lastOutboundAt())->diffForHumans() ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Error terakhir</div>
                <div>{{ optional($account->lastOutboundErrorAt())->diffForHumans() ?? '-' }}</div>
            </div>
            @if($account->lastOutboundErrorMessage())
                <div class="col-12">
                    <div class="text-muted small">Pesan error terakhir</div>
                    <div class="small text-danger">{{ $account->lastOutboundErrorMessage() }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endunless
@endsection
