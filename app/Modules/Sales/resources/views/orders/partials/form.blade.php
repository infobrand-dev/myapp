@php
    $orderTotals = is_array($order->totals_snapshot ?? null) ? $order->totals_snapshot : [];
    $headerDiscountTotal = old('header_discount_total', data_get($orderTotals, 'header_discount_total', 0));
    $selectedSalesTaxId = old('tax_rate_id', data_get($order->meta, 'tax.tax_rate_id'));
    $orderItems = old('items', $order->items->map(function ($item) {
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

    if (empty($orderItems)) {
        $orderItems = [[
            'sellable_key' => '',
            'qty' => 1,
            'unit_price' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'notes' => '',
        ]];
    }

    $cancelRoute = $order->exists ? route('sales.orders.show', $order) : route('sales.orders.index');
    $sellablesByKey = collect($sellables)->keyBy('key');
@endphp

<script>
window._sellables = @json($sellables->map(fn($p) => [
    'key' => $p['key'],
    'label' => $p['label'],
    'description' => $p['description'],
    'unit_price' => $p['unit_price'],
])->values());
</script>

<form method="POST" action="{{ $submitRoute }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif
    <input type="hidden" name="header_tax_total" value="0">

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Sales Order Info</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <x-contact-select
                            name="contact_id"
                            label="Customer"
                            placeholder="Guest / Walk-in"
                            :value="old('contact_id', $order->contact_id)"
                            :value-name="$order->contact?->name"
                            :value-type="$order->contact?->type"
                        />
                    </div>
                    <div class="col-md-6"><label class="form-label">Order Date</label><input type="datetime-local" name="order_date" class="form-control @error('order_date') is-invalid @enderror" value="{{ old('order_date', optional($order->order_date)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">@error('order_date') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    <div class="col-md-6"><label class="form-label">Expected Delivery Date</label><input type="date" name="expected_delivery_date" class="form-control @error('expected_delivery_date') is-invalid @enderror" value="{{ old('expected_delivery_date', optional($order->expected_delivery_date)->format('Y-m-d')) }}">@error('expected_delivery_date') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    <div class="col-md-6"><label class="form-label">Currency</label><input type="text" name="currency_code" maxlength="3" class="form-control @error('currency_code') is-invalid @enderror" value="{{ old('currency_code', $order->currency_code ?: app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency()) }}">@error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    @if(($salesTaxOptions ?? collect())->isNotEmpty())
                        <div class="col-md-6">
                            <label class="form-label">Tax Master</label>
                            <select name="tax_rate_id" class="form-select @error('tax_rate_id') is-invalid @enderror">
                                <option value="">Tanpa tax master</option>
                                @foreach($salesTaxOptions as $taxOption)
                                    <option value="{{ $taxOption->id }}" @selected((string) $selectedSalesTaxId === (string) $taxOption->id)>{{ $taxOption->name }} ({{ $taxOption->code }}) - {{ number_format((float) $taxOption->rate_percent, 2) }}%</option>
                                @endforeach
                            </select>
                            @error('tax_rate_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
                    <div class="col-md-6"><label class="form-label">Header Discount</label><input type="number" min="0" step="0.01" name="header_discount_total" class="form-control @error('header_discount_total') is-invalid @enderror" value="{{ $headerDiscountTotal }}">@error('header_discount_total') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $order->notes) }}</textarea>@error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                    <div class="col-12"><label class="form-label">Customer Note</label><textarea name="customer_note" rows="2" class="form-control @error('customer_note') is-invalid @enderror">{{ old('customer_note', $order->customer_note) }}</textarea>@error('customer_note') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Order Items</h3><div class="card-options"><button type="button" class="btn btn-sm btn-outline-primary" data-add-sale-item>Add Item</button></div></div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <div data-sale-items>
                        @foreach($orderItems as $index => $item)
                            @php $sel = $sellablesByKey->get($item['sellable_key'] ?? ''); @endphp
                            <div class="border rounded p-3 mb-3 sale-item-row">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label">Product / Variant</label>
                                        @include('shared.product-autocomplete-field', [
                                            'keyName' => "items[{$index}][sellable_key]",
                                            'selectedKey' => $item['sellable_key'] ?? '',
                                            'selectedLabel' => $sel['label'] ?? '',
                                            'selectedDescription' => $sel['description'] ?? '',
                                        ])
                                    </div>
                                    <div class="col-md-2"><label class="form-label">Qty</label><input type="number" min="0.0001" step="0.0001" name="items[{{ $index }}][qty]" class="form-control" value="{{ $item['qty'] }}"></div>
                                    <div class="col-md-2"><label class="form-label">Unit Price</label><input type="number" min="0" step="0.01" name="items[{{ $index }}][unit_price]" class="form-control" value="{{ $item['unit_price'] }}" data-item-price></div>
                                    <div class="col-md-1"><label class="form-label">Disc.</label><input type="number" min="0" step="0.01" name="items[{{ $index }}][discount_total]" class="form-control" value="{{ $item['discount_total'] }}"></div>
                                    <div class="col-md-1"><label class="form-label">Tax</label><input type="number" min="0" step="0.01" name="items[{{ $index }}][tax_total]" class="form-control" value="{{ $item['tax_total'] }}"></div>
                                    <div class="col-md-1 d-flex align-items-end justify-content-end"><button type="button" class="btn btn-icon btn-sm btn-outline-danger" data-remove-sale-item><i class="ti ti-trash"></i></button></div>
                                    <div class="col-12"><label class="form-label">Item Notes</label><input type="text" name="items[{{ $index }}][notes]" class="form-control" value="{{ $item['notes'] }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2"><a href="{{ $cancelRoute }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-primary">Save Sales Order</button></div>
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
                    <label class="form-label">Product / Variant</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search by name or SKU..." autocomplete="off" data-item-search>
                        <input type="hidden" name="items[${index}][sellable_key]" value="" data-item-key>
                        <div class="position-absolute w-100 border rounded bg-white shadow-sm d-none" style="top:calc(100% + 2px); z-index:1050; max-height:240px; overflow-y:auto;" data-item-dropdown></div>
                    </div>
                    <div class="form-hint" data-item-hint>Type to search - default price will be filled automatically.</div>
                </div>
                <div class="col-md-2"><label class="form-label">Qty</label><input type="number" min="0.0001" step="0.0001" name="items[${index}][qty]" class="form-control" value="1"></div>
                <div class="col-md-2"><label class="form-label">Unit Price</label><input type="number" min="0" step="0.01" name="items[${index}][unit_price]" class="form-control" value="0" data-item-price></div>
                <div class="col-md-1"><label class="form-label">Disc.</label><input type="number" min="0" step="0.01" name="items[${index}][discount_total]" class="form-control" value="0"></div>
                <div class="col-md-1"><label class="form-label">Tax</label><input type="number" min="0" step="0.01" name="items[${index}][tax_total]" class="form-control" value="0"></div>
                <div class="col-md-1 d-flex align-items-end justify-content-end"><button type="button" class="btn btn-icon btn-sm btn-outline-danger" data-remove-sale-item><i class="ti ti-trash"></i></button></div>
                <div class="col-12"><label class="form-label">Item Notes</label><input type="text" name="items[${index}][notes]" class="form-control" value=""></div>
            </div>
        </div>`;

    ProductAutocomplete.init({
        items: window._sellables || [],
        priceField: 'unit_price',
        wrapperAttr: 'data-sale-items',
        rowClass: 'sale-item-row',
        addBtnAttr: 'data-add-sale-item',
        removeBtnAttr: 'data-remove-sale-item',
        newRowHtml,
    });
});
</script>
@endpush
