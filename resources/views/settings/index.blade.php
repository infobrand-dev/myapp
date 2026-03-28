@extends('layouts.admin')

@section('content')
@php
    $section = $sections[$currentSection] ?? $sections['general'];
@endphp

<div class="page-header d-flex align-items-start align-items-md-center justify-content-between flex-column flex-md-row gap-3">
    <div>
        <div class="page-pretitle">Konfigurasi</div>
        <h2 class="page-title">Settings</h2>
        <div class="text-muted small mt-1">{{ $section['description'] }}</div>
    </div>
    @if(!empty($settingsStats))
    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
        @foreach($settingsStats as $stat)
            <span class="badge bg-blue-lt text-blue px-3 py-2">{{ $stat['label'] }}: {{ $stat['value'] }}</span>
        @endforeach
    </div>
    @endif
</div>

<div class="row g-3">
    <div class="col-xl-3">
        @include('settings.partials.nav')
    </div>
    <div class="col-xl-9">
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
