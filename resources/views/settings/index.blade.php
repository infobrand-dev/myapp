@extends('layouts.admin')

@section('content')
@php
    $section = $sections[$currentSection] ?? $sections['general'];
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
    <div>
        <h2 class="mb-1">Settings</h2>
        <div class="text-muted small">{{ $section['description'] }}</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @foreach($settingsStats as $stat)
            <span class="badge bg-blue-lt text-blue px-3 py-2">{{ $stat['label'] }}: {{ $stat['value'] }}</span>
        @endforeach
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-3">
        @include('settings.partials.nav')
    </div>
    <div class="col-xl-9">
        @include('settings.partials.overview')
        @includeIf('settings.partials.sections.' . $currentSection)
    </div>
</div>
@endsection
