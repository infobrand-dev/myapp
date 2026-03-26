@php
    $saleItems = old('items', $sale->items->map(function ($item) {
        $key = $item->product_variant_id ? 'variant:' . $item->product_variant_id : 'product:' . $item->product_id;
        return [
            'sellable_key' => $key,
            'qty' => $item->qty,
            'unit_price' => $item->unit_price,
            'discount_total' => $item->discount_total,
            'tax_total' => $item->tax_total,
            'notes' => $item->notes,
        ];
    })->all());

    if (empty($saleItems)) {
        $saleItems = [[
            'sellable_key' => '',
            'qty' => 1,
            'unit_price' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'notes' => '',
        ]];
    }
@endphp

<form method="POST" action="{{ $submitRoute }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Header Sales</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Customer</label>
                        <select name="contact_id" class="form-select">
                            <option value="">Guest / Walk-in</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('contact_id', $sale->contact_id) === (string) $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Source</label>
                        <select name="source" class="form-select">
                            @foreach($sourceOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('source', $sale->source) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            @foreach($paymentStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_status', $sale->payment_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Transaction Date</label>
                        <input type="datetime-local" name="transaction_date" class="form-control" value="{{ old('transaction_date', optional($sale->transaction_date)->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency_code" class="form-control" maxlength="3" value="{{ old('currency_code', $sale->currency_code ?: 'IDR') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">External Reference</label>
                        <input type="text" name="external_reference" class="form-control" value="{{ old('external_reference', $sale->external_reference) }}" placeholder="Opsional, untuk POS/API/online ref">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="5">{{ old('notes', $sale->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Items</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-add-sale-item>Add Item</button>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div data-sale-items>
                        @foreach($saleItems as $index => $item)
                            @php
                                $selectedSellable = collect($sellables)->firstWhere('key', $item['sellable_key'] ?? '');
                            @endphp
                            <div class="border rounded p-3 mb-3 sale-item-row">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Product / Variant</label>
                                        <select name="items[{{ $index }}][sellable_key]" class="form-select" data-sellable-select>
                                            <option value="">Pilih item</option>
                                            @foreach($sellables as $sellable)
                                                <option value="{{ $sellable['key'] }}" data-default-price="{{ $sellable['unit_price'] }}" @selected(($item['sellable_key'] ?? '') === $sellable['key'])>{{ $sellable['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-hint" data-sellable-description>{{ $selectedSellable['description'] ?? 'Harga default akan terisi otomatis, lalu bisa diubah jika perlu.' }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Qty</label>
                                        <input type="number" min="0.0001" step="0.0001" name="items[{{ $index }}][qty]" class="form-control" value="{{ $item['qty'] }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Unit Price</label>
                                        <input type="number" min="0" step="0.01" name="items[{{ $index }}][unit_price]" class="form-control" value="{{ $item['unit_price'] }}">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Disc</label>
                                        <input type="number" min="0" step="0.01" name="items[{{ $index }}][discount_total]" class="form-control" value="{{ $item['discount_total'] }}">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Tax</label>
                                        <input type="number" min="0" step="0.01" name="items[{{ $index }}][tax_total]" class="form-control" value="{{ $item['tax_total'] }}">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger w-100" data-remove-sale-item>Remove</button>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Item Notes</label>
                                        <input type="text" name="items[{{ $index }}][notes]" class="form-control" value="{{ $item['notes'] }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <div class="text-muted small">Total dihitung otomatis oleh server.</div>
                    <button type="submit" class="btn btn-primary">Save Draft</button>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="sale-item-template">
    <div class="border rounded p-3 mb-3 sale-item-row">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Product / Variant</label>
                <select name="items[__INDEX__][sellable_key]" class="form-select" data-sellable-select>
                    <option value="">Pilih item</option>
                    @foreach($sellables as $sellable)
                        <option value="{{ $sellable['key'] }}" data-default-price="{{ $sellable['unit_price'] }}">{{ $sellable['label'] }}</option>
                    @endforeach
                </select>
                <div class="form-hint" data-sellable-description>Harga default akan terisi otomatis, lalu bisa diubah jika perlu.</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Qty</label>
                <input type="number" min="0.0001" step="0.0001" name="items[__INDEX__][qty]" class="form-control" value="1">
            </div>
            <div class="col-md-2">
                <label class="form-label">Unit Price</label>
                <input type="number" min="0" step="0.01" name="items[__INDEX__][unit_price]" class="form-control" value="0">
            </div>
            <div class="col-md-1">
                <label class="form-label">Disc</label>
                <input type="number" min="0" step="0.01" name="items[__INDEX__][discount_total]" class="form-control" value="0">
            </div>
            <div class="col-md-1">
                <label class="form-label">Tax</label>
                <input type="number" min="0" step="0.01" name="items[__INDEX__][tax_total]" class="form-control" value="0">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger w-100" data-remove-sale-item>Remove</button>
            </div>
            <div class="col-12">
                <label class="form-label">Item Notes</label>
                <input type="text" name="items[__INDEX__][notes]" class="form-control" value="">
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
(() => {
    const wrapper = document.querySelector('[data-sale-items]');
    const addButton = document.querySelector('[data-add-sale-item]');
    const template = document.getElementById('sale-item-template');

    if (!wrapper || !addButton || !template) {
        return;
    }

    const bindRow = (row) => {
        row.querySelector('[data-remove-sale-item]')?.addEventListener('click', () => {
            if (wrapper.querySelectorAll('.sale-item-row').length === 1) {
                return;
            }
            row.remove();
            reindex();
        });

        row.querySelector('[data-sellable-select]')?.addEventListener('change', (event) => {
            const selected = event.target.selectedOptions[0];
            const priceInput = row.querySelector('input[name$="[unit_price]"]');
            const description = row.querySelector('[data-sellable-description]');

            if (selected?.dataset.defaultPrice && priceInput && (!priceInput.value || priceInput.value === '0')) {
                priceInput.value = selected.dataset.defaultPrice;
            }

            if (description) {
                description.textContent = selected?.textContent?.trim() || 'Harga default akan terisi otomatis, lalu bisa diubah jika perlu.';
            }
        });
    };

    const reindex = () => {
        wrapper.querySelectorAll('.sale-item-row').forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/items\\[\\d+\\]/, `items[${index}]`);
            });
        });
    };

    wrapper.querySelectorAll('.sale-item-row').forEach(bindRow);

    addButton.addEventListener('click', () => {
        const index = wrapper.querySelectorAll('.sale-item-row').length;
        const html = template.innerHTML.replace(/__INDEX__/g, String(index));
        const fragment = document.createRange().createContextualFragment(html);
        wrapper.appendChild(fragment);
        bindRow(wrapper.lastElementChild);
    });
})();
</script>
@endpush
