@extends('emails.layout')

@section('subject', 'Test transactional email - ' . $workspaceName)

@section('content')
    <h1>Test transactional email berhasil</h1>
    <p>Konfigurasi SMTP transactional untuk workspace <strong>{{ $workspaceName }}</strong> berhasil dipakai mengirim email.</p>
    <div class="info-box">
        From: {{ $fromEmail }}<br>
        Reply-To: {{ $replyToEmail ?: '-' }}
    </div>
@endsection
