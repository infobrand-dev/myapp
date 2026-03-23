@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success d-flex align-items-center gap-2']) }} role="alert">
        <i class="ti ti-circle-check flex-shrink-0" aria-hidden="true"></i>
        <span>{{ $status }}</span>
    </div>
@endif
