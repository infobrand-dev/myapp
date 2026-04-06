@extends('layouts.admin')

@section('content')
@php
    $section = $sections[$currentSection] ?? $sections['general'];
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Konfigurasi</div>
            <h2 class="page-title">Settings</h2>
            <p class="text-muted mb-0">{{ $section['description'] }}</p>
        </div>
        @if(!empty($settingsStats))
        <div class="col-auto d-flex flex-wrap gap-2">
            @foreach($settingsStats as $stat)
                <span class="badge bg-blue-lt text-blue px-3 py-2">{{ $stat['label'] }}: {{ $stat['value'] }}</span>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        @if($errors->any())
            <div class="alert alert-danger mb-3">
                <div class="fw-semibold mb-2">Periksa input berikut:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @include('settings.partials.overview')
        @includeIf('settings.partials.sections.' . $currentSection)
    </div>
</div>
@endsection
