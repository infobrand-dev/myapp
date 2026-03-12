@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WhatsApp Flows</h2>
        <div class="text-muted small">Draft dan sinkronisasi Flow JSON ke Meta WhatsApp Flows API.</div>
    </div>
    <a href="{{ route('whatsapp-api.flows.create') }}" class="btn btn-primary">Tambah Flow</a>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Instance</th>
                    <th>Meta Flow ID</th>
                    <th>Status</th>
                    <th>Validation</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($flows as $flow)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $flow->name }}</div>
                            <div class="text-muted small">{{ collect($flow->categories ?? [])->join(', ') ?: '-' }}</div>
                        </td>
                        <td>{{ $flow->instance?->name ?? '-' }}</td>
                        <td><code>{{ $flow->meta_flow_id ?: '-' }}</code></td>
                        <td><span class="badge bg-secondary-lt text-secondary">{{ strtoupper($flow->status ?? 'draft') }}</span></td>
                        <td>
                            @php($errorCount = count($flow->validation_errors ?? []))
                            @if($errorCount > 0)
                                <span class="badge bg-red-lt text-red">{{ $errorCount }} error</span>
                            @else
                                <span class="badge bg-green-lt text-green">OK</span>
                            @endif
                        </td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                <a href="{{ route('whatsapp-api.flows.edit', $flow) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.flows.sync', $flow) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Sync</button>
                                </form>
                                <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.flows.publish', $flow) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success" type="submit" {{ !$flow->meta_flow_id ? 'disabled' : '' }}>Publish</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada flow.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $flows->links() }}</div>
@endsection
