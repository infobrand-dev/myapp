@props(['errors'])

@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'alert alert-danger']) }} role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="ti ti-alert-circle mt-1 flex-shrink-0" aria-hidden="true"></i>
            <div>
                <div class="fw-semibold mb-1">{{ __('Please fix the following errors:') }}</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
