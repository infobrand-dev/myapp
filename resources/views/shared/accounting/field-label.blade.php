@php
    $label = $label ?? '';
    $tooltip = isset($tooltip) ? trim((string) $tooltip) : '';
    $required = (bool) ($required ?? false);
    $icon = $icon ?? 'ti-info-circle';
    $class = trim((string) ($class ?? ''));
    $tooltipPlacement = $tooltipPlacement ?? 'top';
@endphp

<label class="form-label d-inline-flex align-items-center gap-1 {{ $class }}">
    <span>{{ $label }}</span>
    @if($required)
        <span class="text-danger">*</span>
    @endif
    @if($tooltip !== '')
        <span
            tabindex="0"
            class="text-muted"
            role="button"
            data-bs-toggle="tooltip"
            data-bs-placement="{{ $tooltipPlacement }}"
            data-bs-trigger="hover focus"
            title="{{ $tooltip }}"
            style="cursor:help; line-height:1;"
            aria-label="{{ $label }} help"
        >
            <i class="ti {{ $icon }}"></i>
        </span>
    @endif
</label>

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const initializeTooltips = function (root) {
            if (!(window.bootstrap && bootstrap.Tooltip)) {
                return;
            }

            (root || document).querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
                bootstrap.Tooltip.getOrCreateInstance(element);
            });
        };

        window.AccountingTooltips = window.AccountingTooltips || {
            refresh(root) {
                initializeTooltips(root instanceof Element ? root : document);
            }
        };

        initializeTooltips(document);

        if ('MutationObserver' in window) {
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (!(node instanceof Element)) {
                            return;
                        }

                        if (node.matches('[data-bs-toggle="tooltip"]')) {
                            initializeTooltips(node.parentElement || document);
                            return;
                        }

                        if (node.querySelector('[data-bs-toggle="tooltip"]')) {
                            initializeTooltips(node);
                        }
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        }
    });
    </script>
    @endpush
@endonce
