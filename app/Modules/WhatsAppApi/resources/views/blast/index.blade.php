@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WA Blast Campaigns</h2>
        <div class="text-muted small">Blast template WhatsApp Cloud API dengan queue internal.</div>
    </div>
    <a href="{{ route('whatsapp-api.blast-campaigns.create') }}" class="btn btn-primary">Buat Campaign</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Instance</th>
                    <th>Template</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Jadwal</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $cp)
                    @php
                        $statusClass = match($cp->status) {
                            'done' => 'bg-green-lt text-green',
                            'running' => 'bg-blue-lt text-blue',
                            'scheduled' => 'bg-yellow-lt text-yellow',
                            'failed' => 'bg-red-lt text-red',
                            default => 'bg-secondary-lt text-secondary',
                        };
                    @endphp
                    <tr>
                        <td class="fw-bold">{{ $cp->name }}</td>
                        <td>{{ $cp->instance?->name ?? '-' }}</td>
                        <td>{{ $cp->template?->name ?? '-' }} <span class="text-muted">({{ $cp->template?->language }})</span></td>
                        <td><span class="badge {{ $statusClass }}">{{ strtoupper($cp->status) }}</span></td>
                        <td class="small">
                            Total: {{ $cp->total_count }}<br>
                            Sent: {{ $cp->sent_count }} | Failed: {{ $cp->failed_count }} | Queue: {{ $cp->queued_count }}
                        </td>
                        <td>{{ optional($cp->scheduled_at)->format('d M Y H:i') ?? '-' }}</td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                @if(in_array($cp->status, ['draft', 'scheduled', 'failed', 'done'], true))
                                    <form method="POST" action="{{ route('whatsapp-api.blast-campaigns.launch', $cp) }}" class="d-inline-block m-0">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary btn-icon" title="Launch" aria-label="Launch" type="submit">
                                            <i class="ti ti-player-play icon"></i>
                                        </button>
                                    </form>
                                @endif

                                @if($cp->failed_count > 0)
                                    <form method="POST" action="{{ route('whatsapp-api.blast-campaigns.retry-failed', $cp) }}" class="d-inline-block m-0">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-azure btn-icon" title="Retry failed" aria-label="Retry failed" type="submit">
                                            <i class="ti ti-refresh icon"></i>
                                        </button>
                                    </form>
                                @endif

                                @if($cp->status !== 'running')
                                    <form method="POST" action="{{ route('whatsapp-api.blast-campaigns.destroy', $cp) }}" class="d-inline-block m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger btn-icon" title="Delete" aria-label="Delete" type="submit" data-confirm="Hapus campaign ini?">
                                            <i class="ti ti-trash icon"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">Belum ada campaign blast.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $campaigns->links() }}</div>
@endsection
