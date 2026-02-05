@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Email Marketing</h2>
        <div class="text-muted small">Kelola kampanye email, kirim sekarang atau jadwalkan.</div>
    </div>
    <a href="{{ route('email-marketing.create') }}" class="btn btn-primary">Buat Campaign</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter mb-0">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Status</th>
                    <th class="text-center">Recipients</th>
                    <th>Jadwal</th>
                    <th class="text-end">Diperbarui</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td>
                            <a href="{{ route('email-marketing.show', $campaign) }}" class="fw-semibold">
                                {{ $campaign->subject }}
                            </a>
                        </td>
                        <td>
                            <span class="badge
                                @if($campaign->status === 'running') bg-green-lt text-green
                                @elseif($campaign->status === 'scheduled') bg-amber-lt text-amber
                                @else bg-secondary-lt text-secondary @endif">
                                {{ strtoupper($campaign->status) }}
                            </span>
                        </td>
                        @php
                            $recipientsDisplay = $campaign->status === 'running'
                                ? ($campaign->recipients_count ?? 0)
                                : ($campaign->planned_count ?? $campaign->recipients_count ?? 0);
                        @endphp
                        <td class="text-center">{{ $recipientsDisplay }}</td>
                        <td>
                            @if($campaign->scheduled_at)
                                {{ $campaign->scheduled_at->format('d M Y H:i') }}
                            @elseif($campaign->started_at)
                                Dikirim {{ $campaign->started_at->diffForHumans() }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end text-muted small">{{ $campaign->updated_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada campaign email.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
