@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WhatsApp API - Chat</h2>
        <div class="text-muted small">Halaman placeholder chat WhatsApp API.</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('whatsapp-api.settings') }}">Settings</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="text-muted">
            Chat interface for WhatsApp API belum diimplementasikan. Silakan gunakan WhatsApp Bro untuk chat sementara.
        </div>
    </div>
@endsection
