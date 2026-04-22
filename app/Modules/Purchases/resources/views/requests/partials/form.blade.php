@php
    $requestTotals = is_array($requestModel->totals_snapshot ?? null) ? $requestModel->totals_snapshot : [];
    $selectedPurchaseTaxId = old('tax_rate_id', data_get($requestModel->meta, 'tax.tax_rate_id'));
    $requestItems = old('items', $requestModel->items->map(function ($item) {
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

    if (empty($requestItems)) {
        $requestItems = [[
            'purchasable_key' => '',
            'qty' => 1,
            'unit_cost' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'notes' => '',
        ]];
    }

    $cancelRoute = $requestModel->exists ? route('purchases.requests.show', $requestModel) : route('purchases.requests.index');
    $purchasablesByKey = collect($purchasables)->keyBy('key');
@endphp

<script>
window._purchasables = @json(collect($purchasables)->map(function($p) {
    return [
        'key' => $p['key'],
        'label' => $p['label'],
        'description' => $p['description'],
        'unit_cost' => $p['unit_cost'],
    ];
})->values());
</script>

<form method="POST" action="{{ $submitRoute }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Purchase Request Info</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <x-contact-select
                            name="contact_id"
                            label="Supplier"
                            :required="true"
                            placeholder="Select supplier"
                            :value="old('contact_id', $requestModel->contact_id)"
                            :value-name="$requestModel->supplier ? $requestModel->supplier->name : null"
                            :value-type="$requestModel->supplier ? $requestModel->supplier->type : null"
                        />
                    </div>
                    <div class="col-md-6"><label class="form-label">Request Date</label><input type="datetime-local" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', optional($requestModel->request_date)->format('Y-m-d\TH:i') ?: now()->format('Y-m-d\TH:i')) }}">@error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    <div class="col-md-6"><label class="form-label">Needed By Date</label><input type="date" name="expected_receive_date" class="form-control @error('expected_receive_date') is-invalid @enderror" value="{{ old('expected_receive_date', optional($requestModel->needed_by_date)->format('Y-m-d')) }}">@error('expected_receive_date') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    <div class="col-md-6"><label class="form-label">Currency</label><input type="text" name="currency_code" class="form-control @error('currency_code') is-invalid @enderror" value="{{ old('currency_code', $requestModel->currency_code ?: app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency()) }}">@error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    <div class="col-md-6"><label class="form-label">Landed Cost</label><input type="number" min="0" step="0.01" name="landed_cost_total" class="form-control @error('landed_cost_total') is-invalid @enderror" value="{{ old('landed_cost_total', data_get($requestTotals, 'landed_cost_total', $requestModel->landed_cost_total ?? 0)) }}">@error('landed_cost_total') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    @if(($purchaseTaxOptions ?? collect())->isNotEmpty())
                        <div class="col-12">
                            <label class="form-label">Tax Master</label>
                            <select name="tax_rate_id" class="form-select @error('tax_rate_id') is-invalid @enderror">
                                <option value="">Tanpa tax master</option>
                                @foreach($purchaseTaxOptions as $taxOption)
                                    <option value="{{ $taxOption->id }}" @selected((string) $selectedPurchaseTaxId === (string) $taxOption->id)>{{ $taxOption->name }} ({{ $taxOption->code }}) - {{ number_format((float) $taxOption->rate_percent, 2) }}%</option>
                                @endforeach
                            </select>
                            @error('tax_rate_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $requestModel->notes) }}</textarea>@error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    <div class="col-12"><label class="form-label">Internal Notes</label><textarea name="internal_notes" rows="2" class="form-control @error('internal_notes') is-invalid @enderror">{{ old('internal_notes', $requestModel->internal_notes) }}</textarea>@error('internal_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Request Items</h3><div class="card-options"><button type="button" class="btn btn-sm btn-outline-primary" data-add-purchase-item>Add Item</button></div></div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <div data-purchase-items>
                        @foreach($requestItems as $index => $item)
                            @php $sel = $purchasablesByKey->get($item['purchasable_key'] ?? ''); @endphp
                            <div class="border rounded p-3 mb-3 purchase-item-row">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label">Product / Variant</label>
                                        @include('shared.product-autocomplete-field', [
                                            'keyName' => "items[{$index}][purchasable_key]",
                                            'selectedKey' => $item['purchasable_key'] ?? '',
                                            'selectedLabel' => $sel['label'] ?? '',
                                            'selectedDescription' => $sel['description'] ?? '',
                                        ])
                                    </div>
                                    <div class="col-md-2"><label class="form-label">Qty</label><input type="number" min="0.0001" step="0.0001" name="items[{{ $index }}][qty]" class="form-control" value="{{ $item['qty'] }}"></div>
                                    <div class="col-md-2"><label class="form-label">Unit Cost</label><input type="number" min="0" step="0.01" name="items[{{ $index }}][unit_cost]" class="form-control" value="{{ $item['unit_cost'] }}" data-item-price></div>
                                    <div class="col-md-1"><label class="form-label">Disc.</label><input type="number" min="0" step="0.01" name="items[{{ $index }}][discount_total]" class="form-control" value="{{ $item['discount_total'] }}"></div>
                                    <div class="col-md-1"><label class="form-label">Tax</label><input type="number" min="0" step="0.01" name="items[{{ $index }}][tax_total]" class="form-control" value="{{ $item['tax_total'] }}"></div>
                                    <div class="col-md-1 d-flex align-items-end justify-content-end"><button type="button" class="btn btn-icon btn-sm btn-outline-danger" data-remove-purchase-item><i class="ti ti-trash"></i></button></div>
                                    <div class="col-12"><label class="form-label">Item Notes</label><input type="text" name="items[{{ $index }}][notes]" class="form-control" value="{{ $item['notes'] }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2"><a href="{{ $cancelRoute }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-primary">Save Purchase Request</button></div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!window.ProductAutocomplete) return;
    const newRowHtml = (index) => `
        <div class="border rounded p-3 mb-3 purchase-item-row">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Product / Variant</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search by name or SKU..." autocomplete="off" data-item-search>
                        <input type="hidden" name="items[${index}][purchasable_key]" value="" data-item-key>
                        <div class="position-absolute w-100 border rounded bg-white shadow-sm d-none" style="top:calc(100% + 2px); z-index:1050; max-height:240px; overflow-y:auto;" data-item-dropdown></div>
                    </div>
                    <div class="form-hint" data-item-hint>Type to search - default cost will be filled automatically.</div>
                </div>
                <div class="col-md-2"><label class="form-label">Qty</label><input type="number" min="0.0001" step="0.0001" name="items[${index}][qty]" class="form-control" value="1"></div>
                <div class="col-md-2"><label class="form-label">Unit Cost</label><input type="number" min="0" step="0.01" name="items[${index}][unit_cost]" class="form-control" value="0" data-item-price></div>
                <div class="col-md-1"><label class="form-label">Disc.</label><input type="number" min="0" step="0.01" name="items[${index}][discount_total]" class="form-control" value="0"></div>
                <div class="col-md-1"><label class="form-label">Tax</label><input type="number" min="0" step="0.01" name="items[${index}][tax_total]" class="form-control" value="0"></div>
                <div class="col-md-1 d-flex align-items-end justify-content-end"><button type="button" class="btn btn-icon btn-sm btn-outline-danger" data-remove-purchase-item><i class="ti ti-trash"></i></button></div>
                <div class="col-12"><label class="form-label">Item Notes</label><input type="text" name="items[${index}][notes]" class="form-control" value=""></div>
            </div>
        </div>`;

    ProductAutocomplete.init({
        items: window._purchasables || [],
        priceField: 'unit_cost',
        wrapperAttr: 'data-purchase-items',
        rowClass: 'purchase-item-row',
        addBtnAttr: 'data-add-purchase-item',
        removeBtnAttr: 'data-remove-purchase-item',
        newRowHtml,
    });
});
</script>
@endpush
