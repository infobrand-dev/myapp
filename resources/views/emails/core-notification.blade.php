@extends('emails.layout')

@section('subject', '[Alert] ' . $notification->title)

@section('content')
@php
    $primaryAction = data_get($notification->actions, '0.url');
@endphp
<h1>{{ $notification->title }}</h1>

<p>Halo {{ $user->name }},</p>

<p>{{ $notification->body ?: 'Ada notifikasi baru yang membutuhkan perhatian Anda.' }}</p>

@if($primaryAction)
    <div class="btn-wrap">
        <a href="{{ $primaryAction }}" class="btn">Buka Notifikasi</a>
    </div>
@endif

<hr class="divider">

<p>Terima kasih,<br>{{ config('app.name') }}</p>
@endsection
