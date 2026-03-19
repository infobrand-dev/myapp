@php
    $registry = app(\App\Support\ModuleIconRegistry::class);
    $svg = null;

    if (isset($module) && is_array($module)) {
        $svg = $registry->svgForModule($module);
    } elseif (!empty($slug)) {
        $svg = $registry->svgForSlug((string) $slug);
    } elseif (!empty($channel)) {
        $svg = $registry->svgForChannel((string) $channel);
    }

    $size = isset($size) ? (int) $size : 20;
    $class = trim((string) ($class ?? ''));
@endphp

@if($svg)
    <span class="module-svg-icon {{ $class }}" style="width: {{ $size }}px; height: {{ $size }}px;" aria-hidden="true">{!! $svg !!}</span>
@endif
