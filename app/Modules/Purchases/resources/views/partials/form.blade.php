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
@endphp

<form method="POST" action="{{ $submitRoute }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Header Purchase</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Supplier</label>
                        <select name="contact_id" class="form-select" required>
                            <option value="">Pilih supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('contact_id', $purchase->contact_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purchase Date</label>
                        <input type="datetime-local" name="purchase_date" class="form-control" value="{{ old('purchase_date', optional($purchase->purchase_date)->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency_code" class="form-control" value="{{ old('currency_code', $purchase->currency_code ?: 'IDR') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Supplier Reference</label>
                        <input type="text" name="supplier_reference" class="form-control" value="{{ old('supplier_reference', $purchase->supplier_reference) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Supplier Invoice Number</label>
                        <input type="text" name="supplier_invoice_number" class="form-control" value="{{ old('supplier_invoice_number', $purchase->supplier_invoice_number) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $purchase->notes) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Internal Notes</label>
                        <textarea name="internal_notes" class="form-control" rows="3">{{ old('internal_notes', $purchase->internal_notes) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Supplier Notes</label>
                        <textarea name="supplier_notes" class="form-control" rows="3">{{ old('supplier_notes', $purchase->supplier_notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Purchase Items</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-add-purchase-item>Add Item</button>
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

                    <div data-purchase-items>
                        @foreach($purchaseItems as $index => $item)
                            @php($selected = collect($purchasables)->firstWhere('key', $item['purchasable_key'] ?? ''))
                            <div class="border rounded p-3 mb-3 purchase-item-row">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Product / Variant</label>
                                        <select name="items[{{ $index }}][purchasable_key]" class="form-select" data-purchasable-select>
                                            <option value="">Pilih item</option>
                                            @foreach($purchasables as $purchasable)
                                                <option value="{{ $purchasable['key'] }}" data-default-cost="{{ $purchasable['unit_cost'] }}" @selected(($item['purchasable_key'] ?? '') === $purchasable['key'])>{{ $purchasable['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-hint" data-purchasable-description>{{ $selected['description'] ?? 'Cost default akan terisi otomatis dan masih bisa diubah.' }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Qty</label>
                                        <input type="number" min="0.0001" step="0.0001" name="items[{{ $index }}][qty]" class="form-control" value="{{ $item['qty'] }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Unit Cost</label>
                                        <input type="number" min="0" step="0.01" name="items[{{ $index }}][unit_cost]" class="form-control" value="{{ $item['unit_cost'] }}">
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
                                        <button type="button" class="btn btn-outline-danger w-100" data-remove-purchase-item>Remove</button>
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

<template id="purchase-item-template">
    <div class="border rounded p-3 mb-3 purchase-item-row">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Product / Variant</label>
                <select name="items[__INDEX__][purchasable_key]" class="form-select" data-purchasable-select>
                    <option value="">Pilih item</option>
                    @foreach($purchasables as $purchasable)
                        <option value="{{ $purchasable['key'] }}" data-default-cost="{{ $purchasable['unit_cost'] }}">{{ $purchasable['label'] }}</option>
                    @endforeach
                </select>
                <div class="form-hint" data-purchasable-description>Cost default akan terisi otomatis dan masih bisa diubah.</div>
            </div>
            <div class="col-md-2"><label class="form-label">Qty</label><input type="number" min="0.0001" step="0.0001" name="items[__INDEX__][qty]" class="form-control" value="1"></div>
            <div class="col-md-2"><label class="form-label">Unit Cost</label><input type="number" min="0" step="0.01" name="items[__INDEX__][unit_cost]" class="form-control" value="0"></div>
            <div class="col-md-1"><label class="form-label">Disc</label><input type="number" min="0" step="0.01" name="items[__INDEX__][discount_total]" class="form-control" value="0"></div>
            <div class="col-md-1"><label class="form-label">Tax</label><input type="number" min="0" step="0.01" name="items[__INDEX__][tax_total]" class="form-control" value="0"></div>
            <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100" data-remove-purchase-item>Remove</button></div>
            <div class="col-12"><label class="form-label">Item Notes</label><input type="text" name="items[__INDEX__][notes]" class="form-control" value=""></div>
        </div>
    </div>
</template>

@push('scripts')
<script>
(() => {
    const wrapper = document.querySelector('[data-purchase-items]');
    const addButton = document.querySelector('[data-add-purchase-item]');
    const template = document.getElementById('purchase-item-template');
    if (!wrapper || !addButton || !template) return;

    const bindRow = (row) => {
        row.querySelector('[data-remove-purchase-item]')?.addEventListener('click', () => {
            if (wrapper.querySelectorAll('.purchase-item-row').length === 1) return;
            row.remove();
            reindex();
        });

        row.querySelector('[data-purchasable-select]')?.addEventListener('change', (event) => {
            const selected = event.target.selectedOptions[0];
            const costInput = row.querySelector('input[name$="[unit_cost]"]');
            const description = row.querySelector('[data-purchasable-description]');
            if (selected?.dataset.defaultCost && costInput && (!costInput.value || costInput.value === '0')) {
                costInput.value = selected.dataset.defaultCost;
            }
            if (description) {
                description.textContent = selected?.textContent?.trim() || 'Cost default akan terisi otomatis dan masih bisa diubah.';
            }
        });
    };

    const reindex = () => {
        wrapper.querySelectorAll('.purchase-item-row').forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/items\\[\\d+\\]/, `items[${index}]`);
            });
        });
    };

    wrapper.querySelectorAll('.purchase-item-row').forEach(bindRow);
    addButton.addEventListener('click', () => {
        const index = wrapper.querySelectorAll('.purchase-item-row').length;
        const html = template.innerHTML.replace(/__INDEX__/g, String(index));
        const fragment = document.createRange().createContextualFragment(html);
        wrapper.appendChild(fragment);
        bindRow(wrapper.lastElementChild);
    });
})();
</script>
@endpush
