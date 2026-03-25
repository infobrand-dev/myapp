@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $shortlink->title ?: ('Shortlink #' . $shortlink->id) }}</h2>
        <div class="text-muted small">
            /r/{{ optional($shortlink->primaryCode)->code ?? '-' }}
            &mdash; {{ $shortlink->is_active ? 'Aktif' : 'Nonaktif' }}
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('shortlinks.edit', $shortlink) }}" class="btn btn-outline-primary">Edit</a>
        <form method="POST" action="{{ route('shortlinks.destroy', $shortlink) }}">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger" data-confirm="Yakin ingin menghapus shortlink ini?">Hapus</button>
        </form>
        <a href="{{ route('shortlinks.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Detail</h3></div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="text-muted small">URL Tujuan</div>
                    <div class="text-truncate"><a href="{{ $shortlink->destination_url }}" target="_blank" rel="noopener">{{ $shortlink->destination_url }}</a></div>
                </div>
                @if($shortlink->utm_source || $shortlink->utm_medium || $shortlink->utm_campaign)
                    <div class="mb-2">
                        <div class="text-muted small">UTM Parameters</div>
                        @foreach(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $utm)
                            @if($shortlink->$utm)
                                <div class="small">{{ str_replace('utm_', '', $utm) }}: <span class="fw-semibold">{{ $shortlink->$utm }}</span></div>
                            @endif
                        @endforeach
                    </div>
                @endif
                <div>
                    <div class="text-muted small">Total Klik</div>
                    <div class="fs-3 fw-bold">{{ number_format($totalClicks) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Klik 14 Hari Terakhir</h3></div>
            <div class="card-body">
                <canvas id="shortlinkChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kode</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Kode</th><th>Primary</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($shortlink->codes as $code)
                            <tr>
                                <td>
                                    <span class="font-monospace">/r/{{ $code->code }}</span>
                                </td>
                                <td>{{ $code->is_primary ? 'Ya' : '-' }}</td>
                                <td>
                                    @if($code->is_active)
                                        <span class="badge bg-success-lt">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-lt">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">Belum ada kode.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Top Kode</h3></div>
            <div class="list-group list-group-flush">
                @forelse($topCodes as $row)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="small font-monospace">/r/{{ $row->code_used }}</span>
                        <span class="badge text-bg-primary">{{ $row->total }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted small">Belum ada klik</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Top Referer</h3></div>
            <div class="list-group list-group-flush">
                @forelse($topReferrers as $row)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-truncate small" title="{{ $row->referer }}">{{ $row->referer }}</span>
                        <span class="badge text-bg-secondary">{{ $row->total }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted small">Belum ada data</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    const ctx = document.getElementById('shortlinkChart');
    if(!ctx) return;
    const labels = @json($chartLabels);
    const values = @json($chartValues);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Klik',
                data: values,
                backgroundColor: 'rgba(32,107,196,0.5)',
                borderColor: '#206bc4',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
@endpush
