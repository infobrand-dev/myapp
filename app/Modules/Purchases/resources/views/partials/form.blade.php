@php
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
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

    $purchasablesByKey = collect($purchasables)->keyBy('key');
@endphp

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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Purchase Info</h3>
                    @include('shared.accounting.mode-badge')
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
                        @error('contact_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="purchase_date"
                            class="form-control @error('purchase_date') is-invalid @enderror"
                            value="{{ old('purchase_date', optional($purchase->purchase_date)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
                        @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($isAdvancedMode)
                        <div class="col-md-6">
                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                            <input type="text" name="currency_code"
                                class="form-control @error('currency_code') is-invalid @enderror"
                                value="{{ old('currency_code', $purchase->currency_code ?: app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency()) }}">
                            @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <input type="hidden" name="currency_code" value="{{ old('currency_code', $purchase->currency_code ?: app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency()) }}">
                    @endif

                    @if($isAdvancedMode)
                        <div class="col-12">
                            <label class="form-label">Supplier Reference</label>
                            <input type="text" name="supplier_reference"
                                class="form-control @error('supplier_reference') is-invalid @enderror"
                                value="{{ old('supplier_reference', $purchase->supplier_reference) }}">
                            @error('supplier_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Supplier Invoice No.</label>
                            <input type="text" name="supplier_invoice_number"
                                class="form-control @error('supplier_invoice_number') is-invalid @enderror"
                                value="{{ old('supplier_invoice_number', $purchase->supplier_invoice_number) }}">
                            @error('supplier_invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2"
                            class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $purchase->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($isAdvancedMode)
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
                    @endif
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
                            @php $sel = $purchasablesByKey->get($item['purchasable_key'] ?? ''); @endphp
                            <div class="border rounded p-3 mb-3 purchase-item-row">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label">Product / Variant <span class="text-danger">*</span></label>
                                        @include('shared.product-autocomplete-field', [
                                            'keyName'             => "items[{$index}][purchasable_key]",
                                            'selectedKey'         => $item['purchasable_key'] ?? '',
                                            'selectedLabel'       => $sel['label'] ?? '',
                                            'selectedDescription' => $sel['description'] ?? '',
                                        ])
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Qty <span class="text-danger">*</span></label>
                                        <input type="number" min="0.0001" step="0.0001"
                                            name="items[{{ $index }}][qty]" class="form-control"
                                            value="{{ $item['qty'] }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Unit Cost</label>
                                        <input type="number" min="0" step="0.01"
                                            name="items[{{ $index }}][unit_cost]" class="form-control"
                                            value="{{ $item['unit_cost'] }}" data-item-price>
                                    </div>
                                    @if($isAdvancedMode)
                                        <div class="col-md-1">
                                            <label class="form-label">Disc.</label>
                                            <input type="number" min="0" step="0.01"
                                                name="items[{{ $index }}][discount_total]" class="form-control"
                                                value="{{ $item['discount_total'] }}">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">Tax</label>
                                            <input type="number" min="0" step="0.01"
                                                name="items[{{ $index }}][tax_total]" class="form-control"
                                                value="{{ $item['tax_total'] }}">
                                        </div>
                                    @else
                                        <input type="hidden" name="items[{{ $index }}][discount_total]" value="{{ $item['discount_total'] }}">
                                        <input type="hidden" name="items[{{ $index }}][tax_total]" value="{{ $item['tax_total'] }}">
                                    @endif
                                    <div class="col-md-1 d-flex align-items-end justify-content-end">
                                        <button type="button" class="btn btn-icon btn-sm btn-outline-danger"
                                            title="Remove item" data-remove-purchase-item>
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                    @if($isAdvancedMode)
                                        <div class="col-12">
                                            <label class="form-label">Item Notes</label>
                                            <input type="text" name="items[{{ $index }}][notes]"
                                                class="form-control" placeholder="Optional note for this item"
                                                value="{{ $item['notes'] }}">
                                        </div>
                                    @else
                                        <input type="hidden" name="items[{{ $index }}][notes]" value="{{ $item['notes'] }}">
                                    @endif
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
document.addEventListener('DOMContentLoaded', () => {
    if (!window.ProductAutocomplete) return;
    const isAdvancedMode = @json($isAdvancedMode);

    const newRowHtml = (index) => `
        <div class="border rounded p-3 mb-3 purchase-item-row">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Product / Variant <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search by name or SKU…" autocomplete="off" data-item-search>
                        <input type="hidden" name="items[${index}][purchasable_key]" value="" data-item-key>
                        <div class="position-absolute w-100 border rounded bg-white shadow-sm d-none"
                            style="top:calc(100% + 2px); z-index:1050; max-height:240px; overflow-y:auto;"
                            data-item-dropdown></div>
                    </div>
                    <div class="form-hint" data-item-hint>Type to search — default price will be filled automatically.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Qty <span class="text-danger">*</span></label>
                    <input type="number" min="0.0001" step="0.0001" name="items[${index}][qty]" class="form-control" value="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Unit Cost</label>
                    <input type="number" min="0" step="0.01" name="items[${index}][unit_cost]" class="form-control" value="0" data-item-price>
                </div>
                ${isAdvancedMode ? `
                <div class="col-md-1">
                    <label class="form-label">Disc.</label>
                    <input type="number" min="0" step="0.01" name="items[${index}][discount_total]" class="form-control" value="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Tax</label>
                    <input type="number" min="0" step="0.01" name="items[${index}][tax_total]" class="form-control" value="0">
                </div>` : `
                <input type="hidden" name="items[${index}][discount_total]" value="0">
                <input type="hidden" name="items[${index}][tax_total]" value="0">`}
                <div class="col-md-1 d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-icon btn-sm btn-outline-danger" title="Remove item" data-remove-purchase-item>
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
                ${isAdvancedMode ? `
                <div class="col-12">
                    <label class="form-label">Item Notes</label>
                    <input type="text" name="items[${index}][notes]" class="form-control" placeholder="Optional note for this item" value="">
                </div>` : `
                <input type="hidden" name="items[${index}][notes]" value="">`}
            </div>
        </div>`;

    ProductAutocomplete.init({
        items        : window._purchasables || [],
        priceField   : 'unit_cost',
        wrapperAttr  : 'data-purchase-items',
        rowClass     : 'purchase-item-row',
        addBtnAttr   : 'data-add-purchase-item',
        removeBtnAttr: 'data-remove-purchase-item',
        newRowHtml,
    });
});
</script>
@endpush
