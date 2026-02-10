@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Social Media Inbox</h2>
        <div class="text-muted small">Instagram / Facebook DM (placeholder). Integrasi dipisah dari WhatsApp.</div>
    </div>
</div>

<div class="card">
    <div class="card-body text-muted">
        Integrasi belum dihubungkan. Tambahkan adapter DM untuk menyimpan ke conversations dengan channel <code>social_dm</code>.
    </div>
</div>
@endsection
