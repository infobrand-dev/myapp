@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $campaign->name }}</h2>
        <div class="text-secondary">{{ $campaign->subject }}</div>
    </div>
    <a href="{{ route('email-marketing.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">{{ $campaign->status === 'draft' ? 'Edit Body Mail (Drag & Drop)' : 'Body Mail (View Only)' }}</h3>
            </div>
            <div class="card-body">
                @if($campaign->status === 'draft')
                <form method="POST" action="{{ route('email-marketing.update', $campaign) }}" id="campaign-form">
                    @csrf
                    @method('PUT')
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Campaign</label>
                            <input type="text" name="name" class="form-control" value="{{ $campaign->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" value="{{ $campaign->subject }}" required>
                        </div>
                    </div>
                    <input type="hidden" name="body_html" id="body_html">
                    <div id="gjs" style="height: 520px; border:1px solid #e5e7eb;">{!! $campaign->body_html !!}</div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary" id="save-campaign">Simpan Draft</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('email-marketing.launch', $campaign) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-success">Jalankan Campaign</button>
                </form>
                @else
                <div class="border rounded p-3 bg-light mb-3">{!! $campaign->body_html !!}</div>
                <div class="alert alert-info mb-0">Campaign ini sudah berjalan. Body email hanya bisa dilihat.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Laporan Campaign</h3></div>
            <div class="card-body">
                @foreach($metrics as $label => $item)
                <div class="d-flex justify-content-between py-1">
                    <span class="text-capitalize">{{ $label }}</span>
                    <strong>{{ number_format($item['percent'], 2) }}%</strong>
                </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Recipient (Contacts)</h3></div>
            <div class="card-body">
                @if($campaign->recipients->isEmpty())
                    <div class="text-secondary small">Recipient diambil dari module Contacts saat campaign dijalankan.</div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($campaign->recipients->take(10) as $recipient)
                        <div class="list-group-item px-0">
                            <div class="fw-semibold">{{ $recipient->recipient_name }}</div>
                            <div class="small text-secondary">{{ $recipient->recipient_email }}</div>
                            <div class="small text-secondary">Status: {{ $recipient->delivery_status }}</div>
                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                <a href="{{ route('email-marketing.track.open', $recipient->tracking_token) }}" class="btn btn-sm btn-outline-secondary">Simulasi Open</a>
                                <a href="{{ route('email-marketing.track.click', $recipient->tracking_token) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Simulasi Click</a>
                                <form method="POST" action="{{ route('email-marketing.recipients.reply', $recipient) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark Replied</button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@if($campaign->status === 'draft')
@push('scripts')
<script src="https://unpkg.com/grapesjs"></script>
<link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
<script>
    const editor = grapesjs.init({
        container: '#gjs',
        height: '520px',
        fromElement: true,
        storageManager: false,
    });

    const form = document.getElementById('campaign-form');
    form?.addEventListener('submit', function () {
        document.getElementById('body_html').value = editor.getHtml();
    });
</script>
@endpush
@endif
