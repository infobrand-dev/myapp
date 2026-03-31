@props([
    'variant' => 'default',
    'height' => 36,
    'class' => '',
    'alt' => null,
])

@php
    $path = match ($variant) {
        'light', 'white' => 'brand/logo-light.png',
        'dark', 'black' => 'brand/logo-dark.png',
        'vertical' => 'brand/logo-vertical.png',
        'icon' => 'brand/logo-icon.png',
        default => 'brand/logo-default.png',
    };

    $resolvedAlt = $alt ?: config('app.name');
@endphp

<img
    src="{{ asset($path) }}"
    alt="{{ $resolvedAlt }}"
    class="{{ trim($class) }}"
    style="height: {{ (int) $height }}px; width: auto; display: block;"
>
