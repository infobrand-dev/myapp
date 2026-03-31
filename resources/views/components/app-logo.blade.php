@props([
    'variant' => 'default',
    'height' => 36,
    'class' => '',
    'alt' => null,
])

@php
    $resolvedAlt = $alt ?: config('app.name');

    // For auto variant: render two images, CSS shows the right one per theme
    $isAuto = ($variant === 'auto' || $variant === 'default');

    $path = match ($variant) {
        'light', 'white' => 'brand/logo-light.png',
        'dark', 'black'  => 'brand/logo-dark.png',
        'vertical'       => 'brand/logo-vertical.png',
        'icon'           => 'brand/logo-icon.png',
        default          => null, // auto — handled below
    };
@endphp

@if($isAuto)
    {{-- Light mode: default (color) logo; Dark mode: white logo -- swap via CSS --}}
    <span class="app-logo-auto {{ trim($class) }}" style="display:contents;">
        <img src="{{ asset('brand/logo-default.png') }}"
             alt="{{ $resolvedAlt }}"
             class="app-logo-light"
             style="height:{{ (int) $height }}px; width:auto; display:block;">
        <img src="{{ asset('brand/logo-light.png') }}"
             alt="{{ $resolvedAlt }}"
             class="app-logo-dark"
             style="height:{{ (int) $height }}px; width:auto; display:none;">
    </span>
@else
    <img
        src="{{ asset($path) }}"
        alt="{{ $resolvedAlt }}"
        class="{{ trim($class) }}"
        style="height: {{ (int) $height }}px; width: auto; display: block;"
    >
@endif
