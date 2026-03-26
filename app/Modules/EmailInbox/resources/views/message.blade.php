@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $message->subject ?: '(Tanpa subject)' }}</h2>
        <div class="text-muted small">{{ strtoupper($message->direction) }} • {{ strtoupper($message->status) }}</div>
    </div>
    <a href="{{ route('email-inbox.show', $account) }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-2">From</dt>
            <dd class="col-sm-10">{{ $message->from_name ? $message->from_name.' <'.$message->from_email.'>' : ($message->from_email ?? '-') }}</dd>
            <dt class="col-sm-2">To</dt>
            <dd class="col-sm-10">{{ collect($message->to_json)->pluck('email')->implode(', ') ?: '-' }}</dd>
            <dt class="col-sm-2">Sent</dt>
            <dd class="col-sm-10">{{ optional($message->sent_at ?? $message->received_at)->format('d M Y H:i:s') ?: '-' }}</dd>
        </dl>

        @if($message->body_html)
            <div class="border rounded p-3">
                {!! $message->body_html !!}
            </div>
        @else
            <pre class="mb-0">{{ $message->body_text }}</pre>
        @endif
    </div>
</div>
@endsection
