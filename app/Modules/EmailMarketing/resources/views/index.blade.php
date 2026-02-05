@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Email Marketing</h2>
    <a href="{{ route('email-marketing.create') }}" class="btn btn-primary">Buat Campaign</a>
</div>

<div class="row row-cards">
    @forelse($campaigns as $campaign)
    @php($metrics = $campaign->metrics())
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="h3 mb-0">{{ $campaign->name }}</div>
                        <div class="text-secondary">{{ $campaign->subject }}</div>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ $campaign->status === 'running' ? 'bg-green-lt text-green' : 'bg-azure-lt text-azure' }}">{{ strtoupper($campaign->status) }}</span>
                        <div class="small text-secondary mt-1">Recipients: {{ $campaign->recipient_count }}</div>
                    </div>
                </div>

                <div class="row g-2">
                    @foreach($metrics as $label => $item)
                    <div class="col-md-2 col-6">
                        <div class="border rounded p-2">
                            <div class="small text-uppercase text-secondary">{{ $label }}</div>
                            <div class="h4 mb-0">{{ number_format($item['percent'], 2) }}%</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    <a href="{{ route('email-marketing.show', $campaign) }}" class="btn btn-outline-primary btn-sm">Buka Campaign</a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info mb-0">Belum ada campaign email blast.</div>
    </div>
    @endforelse
</div>
@endsection
