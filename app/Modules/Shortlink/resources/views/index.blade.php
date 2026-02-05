@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Shortlink</h2>
        <div class="text-muted small">Buat link pendek, atur UTM, dan lacak klik serta kode.</div>
    </div>
    <a href="{{ route('shortlinks.create') }}" class="btn btn-primary">Tambah Shortlink</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">Ringkasan Klik 14 Hari</div>
                <form method="GET" class="d-flex gap-2">
                    <select name="shortlink_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua shortlink</option>
                        @foreach($allShortlinks as $item)
                            <option value="{{ $item->id }}" {{ $filterId == $item->id ? 'selected' : '' }}>
                                {{ $item->title ?: ($item->primaryCode->code ?? '-tanpa judul-') }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="card-body">
                <canvas id="shortlinkChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
    <div class="card mb-3">
            <div class="card-header">
                <div class="card-title">Top Kode</div>
            </div>
            <div class="list-group list-group-flush">
                @forelse($topCodes as $row)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>/r/{{ $row->code_used }}</span>
                        <span class="badge bg-primary">{{ $row->total }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Belum ada klik</div>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Top Referer</div>
            </div>
            <div class="list-group list-group-flush">
                @forelse($topReferrers as $row)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-truncate" title="{{ $row->referer }}">{{ $row->referer }}</span>
                        <span class="badge bg-secondary">{{ $row->total }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Belum ada data referer</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">Daftar Shortlink</div>
        <a href="{{ route('shortlinks.create') }}" class="btn btn-outline-primary btn-sm d-none d-sm-inline-flex">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
            Shortlink Baru
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Nama / Kode</th>
                    <th>URL Tujuan</th>
                    <th>Kode Lain</th>
                    <th class="text-center">Total Klik</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($shortlinks as $shortlink)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $shortlink->title ?: '-' }}</div>
                            <div class="text-muted small d-flex align-items-center gap-2">
                                <span>/r/{{ optional($shortlink->primaryCode)->code ?? '-' }}</span>
                                @if(optional($shortlink->primaryCode)->code)
                                    <button type="button" class="btn btn-sm btn-outline-secondary copy-shortlink" data-url="{{ url('r/' . $shortlink->primaryCode->code) }}" title="Salin link">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-copy" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /><path d="M4 16a2 2 0 0 1 -2 -2v-8a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2" /></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td style="max-width: 360px;">
                            <div class="text-truncate" title="{{ $shortlink->destination_url }}">{{ $shortlink->destination_url }}</div>
                            @if($shortlink->utm_source || $shortlink->utm_medium || $shortlink->utm_campaign)
                                <div class="text-muted small">UTM:
                                    {{ collect([
                                        $shortlink->utm_source ? 'source='.$shortlink->utm_source : null,
                                        $shortlink->utm_medium ? 'medium='.$shortlink->utm_medium : null,
                                        $shortlink->utm_campaign ? 'campaign='.$shortlink->utm_campaign : null,
                                        $shortlink->utm_term ? 'term='.$shortlink->utm_term : null,
                                        $shortlink->utm_content ? 'content='.$shortlink->utm_content : null,
                                    ])->filter()->implode(', ') }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @foreach($shortlink->codes as $code)
                                <div class="d-flex align-items-center gap-2">
                                    <span class="small {{ $code->is_primary ? 'text-success fw-semibold' : 'text-muted' }}">/r/{{ $code->code }}</span>
                                    <button type="button" class="btn btn-link btn-sm copy-shortlink p-0" data-url="{{ url('r/' . $code->code) }}" title="Salin link">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-copy" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /><path d="M4 16a2 2 0 0 1 -2 -2v-8a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2" /></svg>
                                    </button>
                                </div>
                            @endforeach
                        </td>
                        <td class="text-center">{{ $shortlink->clicks_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('shortlinks.edit', $shortlink) }}" class="btn btn-sm btn-outline-primary">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada shortlink.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-end">
        {{ $shortlinks->appends(['shortlink_id' => $filterId])->links() }}
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
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Klik',
                data: values,
                borderColor: '#206bc4',
                backgroundColor: 'rgba(32,107,196,0.08)',
                tension: 0.25,
                pointRadius: 3,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision:0 } }
            }
        }
    });
})();

(function(){
    function copyText(text){
        if(navigator.clipboard?.writeText){
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function(resolve, reject){
            const ta=document.createElement('textarea');
            ta.value=text;
            ta.style.position='fixed';ta.style.left='-9999px';
            document.body.appendChild(ta);ta.select();
            try{document.execCommand('copy');resolve();}catch(e){reject(e);}finally{document.body.removeChild(ta);}
        });
    }
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.copy-shortlink');
        if(!btn) return;
        const url = btn.getAttribute('data-url');
        const original = btn.innerHTML;
        copyText(url).then(()=>{
            btn.innerHTML = '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"icon\" width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" stroke-width=\"2\" stroke=\"currentColor\" fill=\"none\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/><path d=\"M5 12l5 5l10 -10\" /></svg>';
            btn.classList.add('text-success');
            setTimeout(()=>{ btn.innerHTML = original; btn.classList.remove('text-success'); }, 1200);
        }).catch(()=> alert('Tidak dapat menyalin link. Salin manual: ' + url));
    });
})();
</script>
@endpush
