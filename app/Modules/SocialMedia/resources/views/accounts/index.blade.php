@extends('layouts.tenant')

@section('title', 'Social Accounts')

@php
    $showPlatformOAuthWarnings = $showPlatformOAuthWarnings ?? false;
@endphp

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Social Media</div>
            <h2 class="page-title">Social Accounts</h2>
            <p class="text-muted mb-0">Hubungkan Instagram, Facebook Messenger, X, dan TikTok dari satu tempat.</p>
        </div>
        <div class="col-auto d-flex gap-2 align-items-center flex-wrap">
            @if($showPlatformOAuthWarnings && !($metaOAuthReady ?? false))
                <span class="badge bg-orange-lt text-orange">Meta belum siap</span>
            @endif
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-plug me-1"></i>Connect Channel
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="{{ route('social-media.accounts.connect.meta') }}"
                       class="dropdown-item {{ ($metaOAuthReady ?? false) ? '' : 'disabled' }}">
                        <i class="ti ti-brand-facebook me-2"></i>Facebook / Instagram
                    </a>
                    <a href="{{ route('social-media.accounts.connect.tiktok') }}"
                       class="dropdown-item {{ ($tiktokOAuthReady ?? false) ? '' : 'disabled' }}">
                        <i class="ti ti-brand-tiktok me-2"></i>TikTok
                    </a>
                    @if($xTenantBetaEnabled ?? false)
                        <a href="{{ route('social-media.accounts.connect.x') }}"
                           class="dropdown-item {{ ($xOAuthReady ?? false) ? '' : 'disabled' }}">
                            <i class="ti ti-brand-x me-2"></i>X (Twitter)
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- OAuth Warnings --}}
@if($showPlatformOAuthWarnings && !($metaOAuthReady ?? false))
    <div class="alert alert-warning">
        <div class="d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle mt-1" style="font-size:1.1rem; color:var(--tblr-warning);"></i>
            <div>META OAuth belum siap. Isi <code>META_APP_ID</code> dan <code>META_APP_SECRET</code> di environment platform agar tenant bisa connect akun sosial media tanpa input token manual.</div>
        </div>
    </div>
@endif

@if($showPlatformOAuthWarnings && !($xOAuthReady ?? false) && ($xTenantBetaEnabled ?? false))
    <div class="alert alert-warning">
        <div class="d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle mt-1" style="font-size:1.1rem; color:var(--tblr-warning);"></i>
            <div>X OAuth belum siap. Isi <code>X_API_CLIENT_ID</code> dan <code>X_API_CLIENT_SECRET</code> di environment platform.</div>
        </div>
    </div>
@endif

@if($showPlatformOAuthWarnings && !($tiktokOAuthReady ?? false))
    <div class="alert alert-warning">
        <div class="d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle mt-1" style="font-size:1.1rem; color:var(--tblr-warning);"></i>
            <div>TikTok OAuth belum siap. Isi <code>TIKTOK_API_CLIENT_KEY</code> dan <code>TIKTOK_API_CLIENT_SECRET</code> di environment platform.</div>
        </div>
    </div>
@endif

@if($errors->has('meta_oauth'))
    <div class="alert alert-danger">{{ $errors->first('meta_oauth') }}</div>
@endif

@if($errors->has('x_oauth'))
    <div class="alert alert-danger">{{ $errors->first('x_oauth') }}</div>
@endif

@if($errors->has('tiktok_oauth'))
    <div class="alert alert-danger">{{ $errors->first('tiktok_oauth') }}</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Nama Akun</th>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Aktivitas</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $acc)
                        @php
                            $platformIcons = [
                                'instagram' => 'ti-brand-instagram',
                                'facebook'  => 'ti-brand-facebook',
                                'tiktok'    => 'ti-brand-tiktok',
                                'x'         => 'ti-brand-x',
                            ];
                            $pIcon = $platformIcons[strtolower($acc->platform)] ?? 'ti-brand-social';
                        @endphp
                        <tr>
                            <td>
                                <span class="d-inline-flex align-items-center gap-2">
                                    <i class="ti {{ $pIcon }} text-muted"></i>
                                    <span class="badge bg-blue-lt text-blue">{{ strtoupper($acc->platform) }}</span>
                                </span>
                            </td>
                            <td class="fw-semibold">{{ $acc->name ?: '—' }}</td>
                            <td>
                                <code class="text-muted small">
                                    @if($acc->platform === 'instagram')
                                        {{ $acc->ig_business_id }}
                                    @elseif($acc->platform === 'tiktok')
                                        {{ data_get($acc->metadata, 'tiktok_open_id') ?: '—' }}
                                    @elseif($acc->platform === 'x')
                                        {{ data_get($acc->metadata, 'x_user_id') ?: '—' }}
                                    @else
                                        {{ $acc->page_id }}
                                    @endif
                                </code>
                            </td>
                            <td>
                                @if($acc->status === 'active')
                                    <span class="badge bg-green-lt text-green">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">{{ ucfirst($acc->status) }}</span>
                                @endif
                            </td>
                            <td class="small">
                                <div class="text-muted">
                                    Inbound: {{ optional($acc->lastInboundAt())->diffForHumans() ?? '—' }}
                                    @if($acc->lastInboundSummary())
                                        <div class="text-body">"{{ $acc->lastInboundSummary() }}"</div>
                                    @endif
                                </div>
                                <div class="text-muted mt-1">
                                    Outbound: {{ optional($acc->lastOutboundAt())->diffForHumans() ?? '—' }}
                                </div>
                                @if($acc->lastOutboundErrorAt())
                                    <div class="text-danger mt-1">
                                        <i class="ti ti-alert-circle me-1"></i>Error {{ $acc->lastOutboundErrorAt()->diffForHumans() }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('social-media.accounts.edit', $acc) }}"
                                       class="btn btn-icon btn-sm btn-outline-primary"
                                       title="Pengaturan">
                                        <i class="ti ti-settings"></i>
                                    </a>
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('social-media.accounts.destroy', $acc) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-icon btn-sm btn-outline-danger"
                                                type="submit"
                                                data-confirm="Hapus akun {{ $acc->name ?: $acc->platform }}?">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-brand-instagram text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada akun sosial yang terhubung.</div>
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="ti ti-plug me-1"></i>Connect Channel
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="{{ route('social-media.accounts.connect.meta') }}"
                                           class="dropdown-item {{ ($metaOAuthReady ?? false) ? '' : 'disabled' }}">
                                            Facebook / Instagram
                                        </a>
                                        <a href="{{ route('social-media.accounts.connect.tiktok') }}"
                                           class="dropdown-item {{ ($tiktokOAuthReady ?? false) ? '' : 'disabled' }}">
                                            TikTok
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $accounts->links() }}
    </div>
</div>

@endsection

