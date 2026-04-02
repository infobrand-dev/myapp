@extends('layouts.admin')

@section('content')
<div class="page-header mb-3">
    <div class="row align-items-center w-100">
        <div class="col">
            <h2 class="mb-0">Social Accounts</h2>
            <div class="text-muted small">Hubungkan Facebook Page dan Instagram Business Account tenant melalui Meta OAuth platform.</div>
        </div>
        <div class="col-auto">
            <a href="{{ route('social-media.accounts.connect.meta') }}" class="btn btn-primary {{ ($metaOAuthReady ?? false) ? '' : 'disabled' }}">Hubungkan Meta</a>
        </div>
    </div>
</div>

@if(!($metaOAuthReady ?? false))
    <div class="alert alert-warning">
        META OAuth belum siap. Isi <code>META_APP_ID</code> dan <code>META_APP_SECRET</code> di environment platform agar tenant bisa connect akun sosial media tanpa input token manual.
    </div>
@endif

@if($errors->has('meta_oauth'))
    <div class="alert alert-danger">{{ $errors->first('meta_oauth') }}</div>
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
                        <td>{{ $acc->platform === 'instagram' ? $acc->ig_business_id : $acc->page_id }}</td>
                        <td><span class="badge {{ $acc->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $acc->status }}</span></td>
                        <td class="small text-muted">
                            <div>Inbound: {{ optional($acc->lastInboundAt())->diffForHumans() ?? '—' }}</div>
                            @if($acc->lastInboundSummary())
                                <div class="text-body">“{{ $acc->lastInboundSummary() }}”</div>
                            @endif
                            <div>Outbound: {{ optional($acc->lastOutboundAt())->diffForHumans() ?? '—' }}</div>
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
