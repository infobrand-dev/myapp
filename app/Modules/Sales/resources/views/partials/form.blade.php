@php
    $saleItems = old('items', $sale->items->map(function ($item) {
        $key = $item->product_variant_id ? 'variant:' . $item->product_variant_id : 'product:' . $item->product_id;
        return [
            'sellable_key'   => $key,
            'qty'            => $item->qty,
            'unit_price'     => $item->unit_price,
            'discount_total' => $item->discount_total,
            'tax_total'      => $item->tax_total,
            'notes'          => $item->notes,
        ];
    })->all());

    if (empty($saleItems)) {
        $saleItems = [[
            'sellable_key'   => '',
            'qty'            => 1,
            'unit_price'     => 0,
            'discount_total' => 0,
            'tax_total'      => 0,
            'notes'          => '',
        ]];
    }

    $cancelRoute = $sale->exists
        ? route('sales.show', $sale)
        : route('sales.index');

    $sellablesByKey = collect($sellables)->keyBy('key');
@endphp

<script>
    window._sellables = @json($sellables->map(fn($p) => [
        'key'         => $p['key'],
        'label'       => $p['label'],
        'description' => $p['description'],
        'unit_price'  => $p['unit_price'],
    ])->values());
</script>

<form method="POST" action="{{ $submitRoute }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">

        {{-- Left: Sale Info --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Sale Info</h3>
                </div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Customer</label>
                        <select name="contact_id" class="form-select @error('contact_id') is-invalid @enderror">
                            <option value="">Guest / Walk-in</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('contact_id', $sale->contact_id) === (string) $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('contact_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">Leave empty for walk-in / anonymous customer.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="transaction_date"
                            class="form-control @error('transaction_date') is-invalid @enderror"
                            value="{{ old('transaction_date', optional($sale->transaction_date)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
                        @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Currency <span class="text-danger">*</span></label>
                        <input type="text" name="currency_code" maxlength="3"
                            class="form-control @error('currency_code') is-invalid @enderror"
                            value="{{ old('currency_code', $sale->currency_code ?: app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency()) }}">
                        @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Source</label>
                        <select name="source" class="form-select @error('source') is-invalid @enderror">
                            @foreach($sourceOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('source', $sale->source) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select @error('payment_status') is-invalid @enderror">
                            @foreach($paymentStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_status', $sale->payment_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">External Reference</label>
                        <input type="text" name="external_reference"
                            class="form-control @error('external_reference') is-invalid @enderror"
                            placeholder="POS, API, or online order ref"
                            value="{{ old('external_reference', $sale->external_reference) }}">
                        @error('external_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3"
                            class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $sale->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Sale Items --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Sale Items</h3>
                    <div class="card-options">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-add-sale-item>
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

                    <div data-sale-items>
                        @foreach($saleItems as $index => $item)
                            @php $sel = $sellablesByKey->get($item['sellable_key'] ?? ''); @endphp
                            <div class="border rounded p-3 mb-3 sale-item-row">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label">Product / Variant <span class="text-danger">*</span></label>
                                        @include('shared.product-autocomplete-field', [
                                            'keyName'             => "items[{$index}][sellable_key]",
                                            'selectedKey'         => $item['sellable_key'] ?? '',
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
                                        <label class="form-label">Unit Price</label>
                                        <input type="number" min="0" step="0.01"
                                            name="items[{{ $index }}][unit_price]" class="form-control"
                                            value="{{ $item['unit_price'] }}" data-item-price>
                                    </div>
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
                                    <div class="col-md-1 d-flex align-items-end justify-content-end">
                                        <button type="button" class="btn btn-icon btn-sm btn-outline-danger"
                                            title="Remove item" data-remove-sale-item>
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Item Notes</label>
                                        <input type="text" name="items[{{ $index }}][notes]"
                                            class="form-control" placeholder="Optional note for this item"
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
document.addEventListener('DOMContentLoaded', () => {
    if (!window.ProductAutocomplete) return;

    const newRowHtml = (index) => `
        <div class="border rounded p-3 mb-3 sale-item-row">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Product / Variant <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search by name or SKU…" autocomplete="off" data-item-search>
                        <input type="hidden" name="items[${index}][sellable_key]" value="" data-item-key>
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
                    <label class="form-label">Unit Price</label>
                    <input type="number" min="0" step="0.01" name="items[${index}][unit_price]" class="form-control" value="0" data-item-price>
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
                    <button type="button" class="btn btn-icon btn-sm btn-outline-danger" title="Remove item" data-remove-sale-item>
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
                <div class="col-12">
                    <label class="form-label">Item Notes</label>
                    <input type="text" name="items[${index}][notes]" class="form-control" placeholder="Optional note for this item" value="">
                </div>
            </div>
        </div>`;

    ProductAutocomplete.init({
        items        : window._sellables || [],
        priceField   : 'unit_price',
        wrapperAttr  : 'data-sale-items',
        rowClass     : 'sale-item-row',
        addBtnAttr   : 'data-add-sale-item',
        removeBtnAttr: 'data-remove-sale-item',
        newRowHtml,
    });
});
</script>
@endpush
