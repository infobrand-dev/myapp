@php
    $purchaseItems = old('items', $purchase->items->map(function ($item) {
        $key = $item->product_variant_id ? 'variant:' . $item->product_variant_id : 'product:' . $item->product_id;
        return [
            'purchasable_key' => $key,
            'qty' => $item->qty,
            'unit_cost' => $item->unit_cost,
            'discount_total' => $item->discount_total,
            'tax_total' => $item->tax_total,
            'notes' => $item->notes,
        ];
    })->all());

    if (empty($purchaseItems)) {
        $purchaseItems = [[
            'purchasable_key' => '',
            'qty' => 1,
            'unit_cost' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'notes' => '',
        ]];
    }

    $cancelRoute = $purchase->exists
        ? route('purchases.show', $purchase)
        : route('purchases.index');

    // Build a lookup map for pre-selected items
    $purchasablesByKey = collect($purchasables)->keyBy('key');
@endphp

{{-- Pass purchasables data to JS once --}}
<script>
    window._purchasables = @json($purchasables->map(fn($p) => [
        'key'         => $p['key'],
        'label'       => $p['label'],
        'description' => $p['description'],
        'unit_cost'   => $p['unit_cost'],
    ])->values());
</script>

<form method="POST" action="{{ $submitRoute }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">

        {{-- Left: Purchase Header --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Purchase Info</h3>
                </div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="contact_id" class="form-select @error('contact_id') is-invalid @enderror" required>
                            <option value="">— Select supplier —</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('contact_id', $purchase->contact_id) === (string) $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('contact_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="purchase_date"
                            class="form-control @error('purchase_date') is-invalid @enderror"
                            value="{{ old('purchase_date', optional($purchase->purchase_date)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
                        @error('purchase_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Currency <span class="text-danger">*</span></label>
                        <input type="text" name="currency_code"
                            class="form-control @error('currency_code') is-invalid @enderror"
                            value="{{ old('currency_code', $purchase->currency_code ?: app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency()) }}">
                        @error('currency_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Supplier Reference</label>
                        <input type="text" name="supplier_reference"
                            class="form-control @error('supplier_reference') is-invalid @enderror"
                            value="{{ old('supplier_reference', $purchase->supplier_reference) }}">
                        @error('supplier_reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Supplier Invoice No.</label>
                        <input type="text" name="supplier_invoice_number"
                            class="form-control @error('supplier_invoice_number') is-invalid @enderror"
                            value="{{ old('supplier_invoice_number', $purchase->supplier_invoice_number) }}">
                        @error('supplier_invoice_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2"
                            class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $purchase->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Internal Notes</label>
                        <textarea name="internal_notes" rows="2"
                            class="form-control @error('internal_notes') is-invalid @enderror">{{ old('internal_notes', $purchase->internal_notes) }}</textarea>
                        @error('internal_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">Tidak ditampilkan ke supplier.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Supplier Notes</label>
                        <textarea name="supplier_notes" rows="2"
                            class="form-control @error('supplier_notes') is-invalid @enderror">{{ old('supplier_notes', $purchase->supplier_notes) }}</textarea>
                        @error('supplier_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">Catatan yang akan dilihat supplier.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Purchase Items --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Purchase Items</h3>
                    <div class="card-options">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-add-purchase-item>
                            <i class="ti ti-plus me-1"></i>Add Item
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger mb-3">
                            <div class="d-flex gap-2">
                                <i class="ti ti-alert-circle flex-shrink-0 mt-1"></i>
                                <ul class="mb-0 ps-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div data-purchase-items>
                        @foreach($purchaseItems as $index => $item)
                            @php
                                $selectedItem = $purchasablesByKey->get($item['purchasable_key'] ?? '');
                            @endphp
                            <div class="border rounded p-3 mb-3 purchase-item-row">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label">Product / Variant <span class="text-danger">*</span></label>
                                        <div class="position-relative">
                                            <input type="text"
                                                class="form-control"
                                                placeholder="Search by name or SKU…"
                                                value="{{ $selectedItem['label'] ?? '' }}"
                                                autocomplete="off"
                                                data-purchasable-search>
                                            <input type="hidden"
                                                name="items[{{ $index }}][purchasable_key]"
                                                value="{{ $item['purchasable_key'] ?? '' }}"
                                                data-purchasable-key>
                                            <div class="position-absolute w-100 border rounded bg-white shadow-sm d-none"
                                                style="top:calc(100% + 2px); z-index:1050; max-height:240px; overflow-y:auto;"
                                                data-purchasable-dropdown></div>
                                        </div>
                                        <div class="form-hint" data-purchasable-description>
                                            {{ $selectedItem['description'] ?? 'Type to search — default cost will be filled automatically.' }}
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Qty <span class="text-danger">*</span></label>
                                        <input type="number" min="0.0001" step="0.0001"
                                            name="items[{{ $index }}][qty]"
                                            class="form-control"
                                            value="{{ $item['qty'] }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Unit Cost</label>
                                        <input type="number" min="0" step="0.01"
                                            name="items[{{ $index }}][unit_cost]"
                                            class="form-control"
                                            value="{{ $item['unit_cost'] }}">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Disc.</label>
                                        <input type="number" min="0" step="0.01"
                                            name="items[{{ $index }}][discount_total]"
                                            class="form-control"
                                            value="{{ $item['discount_total'] }}">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Tax</label>
                                        <input type="number" min="0" step="0.01"
                                            name="items[{{ $index }}][tax_total]"
                                            class="form-control"
                                            value="{{ $item['tax_total'] }}">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end justify-content-end">
                                        <button type="button"
                                            class="btn btn-icon btn-sm btn-outline-danger"
                                            title="Remove item"
                                            data-remove-purchase-item>
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Item Notes</label>
                                        <input type="text"
                                            name="items[{{ $index }}][notes]"
                                            class="form-control"
                                            placeholder="Optional note for this item"
                                            value="{{ $item['notes'] }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ $cancelRoute }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Draft
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>

@push('scripts')
<script>
(() => {
    const purchasables = window._purchasables || [];

    // ── Autocomplete logic ──────────────────────────────────────────────────

    function filterPurchasables(query) {
        const q = query.toLowerCase().trim();
        if (!q) return [];
        return purchasables.filter(p =>
            p.label.toLowerCase().includes(q) ||
            p.description.toLowerCase().includes(q)
        ).slice(0, 20);
    }

    function renderDropdown(dropdown, results, onSelect) {
        dropdown.innerHTML = '';

        if (!results.length) {
            dropdown.innerHTML = '<div class="px-3 py-2 text-muted small">No products found.</div>';
            dropdown.classList.remove('d-none');
            return;
        }

        results.forEach((item, i) => {
            const el = document.createElement('div');
            el.className = 'px-3 py-2 border-bottom cursor-pointer';
            el.style.cssText = 'transition:background .1s;';
            el.innerHTML = `<div class="fw-medium small">${escapeHtml(item.label)}</div>`
                + `<div class="text-muted" style="font-size:.75rem;">${escapeHtml(item.description)}</div>`;

            el.addEventListener('mouseenter', () => el.style.background = 'var(--tblr-bg-surface-secondary, #f6f8fb)');
            el.addEventListener('mouseleave', () => el.style.background = '');
            el.addEventListener('mousedown', e => {
                e.preventDefault(); // prevent blur firing before click
                onSelect(item);
            });

            dropdown.appendChild(el);
        });

        dropdown.classList.remove('d-none');
    }

    function closeDropdown(dropdown) {
        dropdown.classList.add('d-none');
        dropdown.innerHTML = '';
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function bindAutocomplete(row) {
        const searchInput  = row.querySelector('[data-purchasable-search]');
        const keyInput     = row.querySelector('[data-purchasable-key]');
        const dropdown     = row.querySelector('[data-purchasable-dropdown]');
        const description  = row.querySelector('[data-purchasable-description]');
        const costInput    = row.querySelector('input[name$="[unit_cost]"]');
        if (!searchInput || !keyInput || !dropdown) return;

        const defaultHint = 'Type to search — default cost will be filled automatically.';

        function selectItem(item) {
            searchInput.value  = item.label;
            keyInput.value     = item.key;
            description.textContent = item.description || defaultHint;
            if (costInput && (!costInput.value || parseFloat(costInput.value) === 0)) {
                costInput.value = item.unit_cost;
            }
            closeDropdown(dropdown);
            searchInput.dataset.selected = '1';
        }

        searchInput.addEventListener('input', () => {
            searchInput.dataset.selected = '';
            keyInput.value = '';
            description.textContent = defaultHint;
            const results = filterPurchasables(searchInput.value);
            if (searchInput.value.trim()) {
                renderDropdown(dropdown, results, selectItem);
            } else {
                closeDropdown(dropdown);
            }
        });

        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim() && !searchInput.dataset.selected) {
                renderDropdown(dropdown, filterPurchasables(searchInput.value), selectItem);
            }
        });

        searchInput.addEventListener('blur', () => {
            // Small delay to allow mousedown on dropdown item to fire first
            setTimeout(() => closeDropdown(dropdown), 150);

            // If user typed something but didn't select, clear the field
            if (!searchInput.dataset.selected && keyInput.value === '') {
                searchInput.value = '';
                description.textContent = defaultHint;
            }
        });

        // Keyboard navigation
        searchInput.addEventListener('keydown', e => {
            const items = dropdown.querySelectorAll('div[class*="px-3"]');
            const active = dropdown.querySelector('[data-active]');
            let idx = active ? [...items].indexOf(active) : -1;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (active) active.removeAttribute('data-active'), active.style.background = '';
                idx = Math.min(idx + 1, items.length - 1);
                items[idx]?.setAttribute('data-active', '1');
                items[idx] && (items[idx].style.background = 'var(--tblr-bg-surface-secondary, #f6f8fb)');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (active) active.removeAttribute('data-active'), active.style.background = '';
                idx = Math.max(idx - 1, 0);
                items[idx]?.setAttribute('data-active', '1');
                items[idx] && (items[idx].style.background = 'var(--tblr-bg-surface-secondary, #f6f8fb)');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (active) active.dispatchEvent(new MouseEvent('mousedown'));
            } else if (e.key === 'Escape') {
                closeDropdown(dropdown);
                searchInput.blur();
            }
        });
    }

    // ── Row add / remove ────────────────────────────────────────────────────

    const wrapper   = document.querySelector('[data-purchase-items]');
    const addButton = document.querySelector('[data-add-purchase-item]');
    if (!wrapper || !addButton) return;

    function newRowHtml(index) {
        return `<div class="border rounded p-3 mb-3 purchase-item-row">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Product / Variant <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" class="form-control"
                            placeholder="Search by name or SKU…"
                            autocomplete="off"
                            data-purchasable-search>
                        <input type="hidden" name="items[${index}][purchasable_key]" value="" data-purchasable-key>
                        <div class="position-absolute w-100 border rounded bg-white shadow-sm d-none"
                            style="top:calc(100% + 2px); z-index:1050; max-height:240px; overflow-y:auto;"
                            data-purchasable-dropdown></div>
                    </div>
                    <div class="form-hint" data-purchasable-description>Type to search — default cost will be filled automatically.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Qty <span class="text-danger">*</span></label>
                    <input type="number" min="0.0001" step="0.0001" name="items[${index}][qty]" class="form-control" value="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Unit Cost</label>
                    <input type="number" min="0" step="0.01" name="items[${index}][unit_cost]" class="form-control" value="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Disc.</label>
                    <input type="number" min="0" step="0.01" name="items[${index}][discount_total]" class="form-control" value="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Tax</label>
                    <input type="number" min="0" step="0.01" name="items[${index}][tax_total]" class="form-control" value="0">
                </div>
                <div class="col-md-1 d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-icon btn-sm btn-outline-danger" title="Remove item" data-remove-purchase-item>
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
                <div class="col-12">
                    <label class="form-label">Item Notes</label>
                    <input type="text" name="items[${index}][notes]" class="form-control" placeholder="Optional note for this item" value="">
                </div>
            </div>
        </div>`;
    }

    function bindRow(row) {
        row.querySelector('[data-remove-purchase-item]')?.addEventListener('click', () => {
            if (wrapper.querySelectorAll('.purchase-item-row').length === 1) return;
            row.remove();
            reindex();
        });
        bindAutocomplete(row);
    }

    function reindex() {
        wrapper.querySelectorAll('.purchase-item-row').forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`);
            });
        });
    }

    // Bind existing rows
    wrapper.querySelectorAll('.purchase-item-row').forEach(bindRow);

    addButton.addEventListener('click', () => {
        const index = wrapper.querySelectorAll('.purchase-item-row').length;
        const fragment = document.createRange().createContextualFragment(newRowHtml(index));
        wrapper.appendChild(fragment);
        bindRow(wrapper.lastElementChild);
        wrapper.lastElementChild.querySelector('[data-purchasable-search]')?.focus();
    });
})();
</script>
@endpush
