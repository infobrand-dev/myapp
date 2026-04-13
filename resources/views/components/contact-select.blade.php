@php
/**
 * x-contact-select — Tom Select AJAX component
 *
 * Props:
 *   name        string   form field name              (required)
 *   label       string   label text                   (default: 'Contact')
 *   required    bool     marks field required          (default: false)
 *   placeholder string   empty option text             (default: '— Select contact —')
 *   value       mixed    current selected id           (default: null)
 *   valueName   string   current selected display name (default: null)
 *   valueType   string   current selected type         (default: null)
 *   hint        string   form-hint below field         (default: null)
 *   showLink    bool     show magnifier link icon      (default: true)
 *   error       string   manual error override         (default: null — uses @error bag)
 *   class       string   extra class on wrapper col    (default: '')
 */

$name        = $name ?? 'contact_id';
$label       = $label ?? 'Contact';
$required    = (bool) ($required ?? false);
$placeholder = $placeholder ?? '— Select contact —';
$hint        = $hint ?? null;
$showLink    = isset($showLink) ? (bool) $showLink : true;
$wrapClass   = $class ?? '';

// Resolve current value — support old() fallback
$currentId   = old($name, $value ?? null);
$currentName = $currentId ? ($valueName ?? null) : null;
$currentType = $currentId ? ($valueType ?? null) : null;

// Unique id so multiple instances don't clash
$uid = 'cselect-' . uniqid();

$searchUrl = Route::has('contacts.search') ? route('contacts.search') : null;
$showField = $searchUrl !== null;
$detailUrl = ($currentId && Route::has('contacts.show'))
    ? route('contacts.show', $currentId)
    : null;
@endphp

@if($showField)
<div class="{{ $wrapClass }}">
    <label class="form-label" for="{{ $uid }}">
        {{ $label }}
        @if($required) <span class="text-danger">*</span> @endif
    </label>
    <div class="d-flex gap-2 align-items-center">
        <div class="flex-grow-1">
            {{-- Hidden select that Tom Select attaches to --}}
            <select
                id="{{ $uid }}"
                name="{{ $name }}"
                class="form-select @error($name) is-invalid @enderror"
                data-contact-select
                data-search-url="{{ $searchUrl }}"
                data-placeholder="{{ $placeholder }}"
                @if($required) required @endif
                aria-label="{{ $label }}"
            >
                {{-- Pre-populate current value so it shows on edit --}}
                @if($currentId)
                    <option value="{{ $currentId }}" selected
                        data-type="{{ $currentType ?? '' }}">{{ $currentName ?? $currentId }}</option>
                @endif
            </select>
            @error($name)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @if($hint)
                <div class="form-hint">{{ $hint }}</div>
            @endif
        </div>

        {{-- Link to open contact detail in new tab --}}
        @if($showLink)
        <a id="{{ $uid }}-link"
           href="{{ $detailUrl ?? '#' }}"
           target="_blank"
           rel="noopener noreferrer"
           class="btn btn-icon btn-outline-secondary flex-shrink-0"
           title="Lihat detail contact"
           style="display:{{ $currentId ? 'inline-flex' : 'none' }};"
        >
            <i class="ti ti-external-link" aria-hidden="true"></i>
        </a>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    'use strict';

    const ICONS = {
        company:    '<i class="ti ti-building" style="font-size:.85rem; color:var(--tblr-azure); margin-right:.35rem;" aria-hidden="true"></i>',
        individual: '<i class="ti ti-user"     style="font-size:.85rem; color:var(--tblr-green); margin-right:.35rem;" aria-hidden="true"></i>',
    };

    function iconFor(type) {
        return ICONS[type] || '<i class="ti ti-user-question" style="font-size:.85rem; color:var(--tblr-secondary); margin-right:.35rem;" aria-hidden="true"></i>';
    }

    function initContactSelect(el) {
        const searchUrl = el.dataset.searchUrl;
        const uid       = el.id;
        const linkEl    = document.getElementById(uid + '-link');

        function updateLink(id) {
            if (!linkEl) return;
            if (!id) {
                linkEl.style.display = 'none';
                linkEl.href = '#';
                return;
            }
            // Build contact detail URL from the search URL base
            const base = searchUrl.replace(/\/search(\?.*)?$/, '');
            linkEl.href = base + '/' + encodeURIComponent(id);
            linkEl.style.display = 'inline-flex';
        }

        if (typeof TomSelect === 'undefined') {
            console.warn('[contact-select] TomSelect not loaded for #' + uid);
            return;
        }

        const ts = new TomSelect(el, {
            valueField:     'id',
            labelField:     'text',
            searchField:    ['text'],
            placeholder:    el.dataset.placeholder,
            loadThrottle:   250,
            preload:        false,
            maxItems:       1,
            openOnFocus:    false,

            load: function (query, callback) {
                if (query.length < 2) { callback(); return; }

                const url = searchUrl + '?q=' + encodeURIComponent(query) + '&limit=25';

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    credentials: 'same-origin',
                })
                    .then(function (r) {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(function (data) { callback(data.results || []); })
                    .catch(function ()   { callback(); });
            },

            render: {
                option: function (item) {
                    return '<div style="display:flex;align-items:center;">' +
                        iconFor(item.type) +
                        '<span>' + (item.text || '') + '</span>' +
                        '</div>';
                },
                item: function (item) {
                    return '<div style="display:flex;align-items:center;">' +
                        iconFor(item.type) +
                        '<span>' + (item.text || '') + '</span>' +
                        '</div>';
                },
                no_results: function () {
                    return '<div class="no-results px-3 py-2 text-muted small">Tidak ada hasil. Coba ketik nama lain.</div>';
                },
                loading: function () {
                    return '<div class="no-results px-3 py-2 text-muted small">Mencari…</div>';
                },
            },

            onChange: function (value) {
                updateLink(value || null);
            },
        });

        // Init link visibility for pre-selected value
        updateLink(el.value || null);
    }

    function boot() {
        document.querySelectorAll('[data-contact-select]').forEach(function (el) {
            if (el._tsInit) return;
            el._tsInit = true;
            initContactSelect(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
@endpush
@endonce

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/tom-select/tom-select.bootstrap5.min.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('vendor/tom-select/tom-select.complete.min.js') }}"></script>
@endpush
@endonce

@else
{{-- Fallback: contacts module not active or route doesn't exist --}}
<div class="{{ $wrapClass }}">
    <label class="form-label" for="{{ $uid }}">{{ $label }}</label>
    <input type="text" id="{{ $uid }}" name="{{ $name }}"
        class="form-control @error($name) is-invalid @enderror"
        value="{{ old($name, $currentId ?? '') }}">
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
@endif
