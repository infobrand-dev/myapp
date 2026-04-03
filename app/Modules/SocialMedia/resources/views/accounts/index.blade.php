@extends('layouts.admin')

@section('content')
<div class="page-header mb-3">
    <div class="row align-items-center w-100">
        <div class="col">
            <h2 class="mb-0">Social Accounts</h2>
            <div class="text-muted small">Hubungkan Instagram, Facebook Messenger, X, dan TikTok dari satu tempat.</div>
        </div>
        <div class="col-auto">
            <div class="d-flex gap-2 align-items-center">
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Connect Channel
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="{{ route('social-media.accounts.connect.meta') }}" class="dropdown-item {{ ($metaOAuthReady ?? false) ? '' : 'disabled' }}">
                            Facebook / Instagram
                        </a>
                        <a href="{{ route('social-media.accounts.connect.tiktok') }}" class="dropdown-item {{ ($tiktokOAuthReady ?? false) ? '' : 'disabled' }}">
                            TikTok
                        </a>
                        @if($xTenantBetaEnabled ?? false)
                            <a href="{{ route('social-media.accounts.connect.x') }}" class="dropdown-item {{ ($xOAuthReady ?? false) ? '' : 'disabled' }}">
                                X
                            </a>
                        @endif
                    </div>
                </div>
                @if(!($metaOAuthReady ?? false))
                    <span class="badge bg-yellow-lt text-yellow-fg">Meta belum siap</span>
                @endif
            </div>
        </div>
    </div>
</div>

@if(!($metaOAuthReady ?? false))
    <div class="alert alert-warning">
        META OAuth belum siap. Isi <code>META_APP_ID</code> dan <code>META_APP_SECRET</code> di environment platform agar tenant bisa connect akun sosial media tanpa input token manual.
    </div>
@endif

@if(!($xOAuthReady ?? false) && ($xTenantBetaEnabled ?? false))
    <div class="alert alert-warning">
        X OAuth belum siap. Isi <code>X_API_CLIENT_ID</code> dan <code>X_API_CLIENT_SECRET</code> di environment platform agar tenant bisa connect akun X tanpa input token manual.
    </div>
@endif

@if(!($tiktokOAuthReady ?? false))
    <div class="alert alert-warning">
        TikTok OAuth belum siap. Isi <code>TIKTOK_API_CLIENT_KEY</code> dan <code>TIKTOK_API_CLIENT_SECRET</code> di environment platform agar tenant bisa connect akun TikTok.
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
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>Nama</th>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Health</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $acc)
                    <tr>
                        <td>{{ strtoupper($acc->platform) }}</td>
                        <td>{{ $acc->name ?: '-' }}</td>
                        <td>
                            @if($acc->platform === 'instagram')
                                {{ $acc->ig_business_id }}
                            @elseif($acc->platform === 'tiktok')
                                {{ data_get($acc->metadata, 'tiktok_open_id') ?: '-' }}
                            @elseif($acc->platform === 'x')
                                {{ data_get($acc->metadata, 'x_user_id') ?: '-' }}
                            @else
                                {{ $acc->page_id }}
                            @endif
                        </td>
                        <td><span class="badge {{ $acc->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $acc->status }}</span></td>
                        <td class="small text-muted">
                            <div>Inbound: {{ optional($acc->lastInboundAt())->diffForHumans() ?? '-' }}</div>
                            @if($acc->lastInboundSummary())
                                <div class="text-body">"{{ $acc->lastInboundSummary() }}"</div>
                            @endif
                            <div>Outbound: {{ optional($acc->lastOutboundAt())->diffForHumans() ?? '-' }}</div>
                            @if($acc->lastOutboundErrorAt())
                                <div class="text-danger">Error: {{ $acc->lastOutboundErrorAt()->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                <a href="{{ route('social-media.accounts.edit', $acc) }}" class="btn btn-outline-secondary btn-sm">Pengaturan</a>
                                <form class="d-inline-block m-0" method="POST" action="{{ route('social-media.accounts.destroy', $acc) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit" data-confirm="Hapus akun?">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada akun.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $accounts->links() }}</div>
@endsection
