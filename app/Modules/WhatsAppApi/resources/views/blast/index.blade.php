@extends('layouts.admin')

@section('title', 'WA Blast Campaigns')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">WhatsApp API</div>
            <h2 class="page-title">Blast Campaigns</h2>
            <p class="text-muted mb-0">Blast template WhatsApp Cloud API dengan queue internal.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('whatsapp-api.blast-campaigns.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Buat Campaign
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
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
                                'done'      => 'bg-green-lt text-green',
                                'running'   => 'bg-blue-lt text-blue',
                                'scheduled' => 'bg-orange-lt text-orange',
                                'failed'    => 'bg-red-lt text-red',
                                default     => 'bg-secondary-lt text-secondary',
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $cp->name }}</td>
                            <td>
                                @if($cp->instance)
                                    <span class="badge bg-azure-lt text-azure">{{ $cp->instance->name }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if($cp->template)
                                    {{ $cp->template->name }}
                                    <span class="text-muted small">({{ $cp->template->language }})</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $statusClass }}">{{ strtoupper($cp->status) }}</span></td>
                            <td>
                                @php
                                    $total = max(1, (int) $cp->total_count);
                                    $sent  = (int) $cp->sent_count;
                                    $pct   = min(100, round($sent / $total * 100));
                                @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px; min-width:80px;">
                                        <div class="progress-bar bg-{{ $cp->status === 'done' ? 'green' : ($cp->status === 'failed' ? 'red' : 'blue') }}"
                                             style="width:{{ $pct }}%"></div>
                                    </div>
                                    <span class="text-muted small text-nowrap">{{ $sent }}/{{ $cp->total_count }}</span>
                                </div>
                                @if((int) $cp->failed_count > 0)
                                    <div class="text-danger small mt-1">{{ $cp->failed_count }} gagal</div>
                                @endif
                            </td>
                            <td class="text-muted small">{{ optional($cp->scheduled_at)->format('d M Y H:i') ?? '—' }}</td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    @if(in_array($cp->status, ['draft', 'scheduled', 'failed', 'done'], true))
                                        <form method="POST" action="{{ route('whatsapp-api.blast-campaigns.launch', $cp) }}" class="d-inline-block m-0">
                                            @csrf
                                            <button class="btn btn-icon btn-sm btn-outline-primary"
                                                    title="Launch"
                                                    type="submit"
                                                    data-confirm="Jalankan campaign {{ $cp->name }}?">
                                                <i class="ti ti-player-play"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if($cp->failed_count > 0)
                                        <form method="POST" action="{{ route('whatsapp-api.blast-campaigns.retry-failed', $cp) }}" class="d-inline-block m-0">
                                            @csrf
                                            <button class="btn btn-icon btn-sm btn-outline-secondary"
                                                    title="Retry failed"
                                                    type="submit">
                                                <i class="ti ti-refresh"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if($cp->status !== 'running')
                                        <form method="POST" action="{{ route('whatsapp-api.blast-campaigns.destroy', $cp) }}" class="d-inline-block m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-icon btn-sm btn-outline-danger"
                                                    title="Hapus"
                                                    type="submit"
                                                    data-confirm="Hapus campaign {{ $cp->name }}?">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="ti ti-speakerphone text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada campaign blast.</div>
                                <a href="{{ route('whatsapp-api.blast-campaigns.create') }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-plus me-1"></i>Buat Campaign Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $campaigns->links() }}
    </div>
</div>

@endsection
