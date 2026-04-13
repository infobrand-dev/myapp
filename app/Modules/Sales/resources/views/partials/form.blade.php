@php
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
    $saleTotals = is_array($sale->totals_snapshot ?? null) ? $sale->totals_snapshot : [];
    $headerDiscountTotal = old('header_discount_total', data_get($saleTotals, 'header_discount_total', 0));
    $headerTaxTotal = old('header_tax_total', data_get($saleTotals, 'header_tax_total', 0));
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Sale Info</h3>
                    @include('shared.accounting.mode-badge')
                </div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <x-contact-select
                            name="contact_id"
                            label="Customer"
                            placeholder="Guest / Walk-in"
                            :value="old('contact_id', $sale->contact_id)"
                            :value-name="$sale->contact?->name"
                            :value-type="$sale->contact?->type"
                            hint="Leave empty for walk-in / anonymous customer."
                        />
                    </div>

                    <div class="col-md-6">
                        @include('shared.accounting.field-label', [
                            'label' => 'Transaction Date',
                            'required' => true,
                            'tooltip' => 'Tanggal dan jam saat penjualan terjadi. Gunakan waktu transaksi sebenarnya agar laporan penjualan dan piutang akurat.',
                        ])
                        <input type="datetime-local" name="transaction_date"
                            class="form-control @error('transaction_date') is-invalid @enderror"
                            value="{{ old('transaction_date', optional($sale->transaction_date)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
                        @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        @include('shared.accounting.field-label', [
                            'label' => 'Due Date',
                            'tooltip' => 'Tanggal jatuh tempo pembayaran penjualan ini. Boleh dikosongkan jika transaksi dibayar langsung saat itu juga.',
                        ])
                        <input type="date" name="due_date"
                            class="form-control @error('due_date') is-invalid @enderror"
                            value="{{ old('due_date', optional($sale->due_date)->format('Y-m-d')) }}">
                        @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($isAdvancedMode)
                        <div class="col-md-6">
                            @include('shared.accounting.field-label', [
                                'label' => 'Currency',
                                'required' => true,
                                'tooltip' => 'Kode mata uang transaksi, misalnya IDR atau USD. Ubah hanya jika memang menerima transaksi dalam mata uang lain.',
                            ])
                            <input type="text" name="currency_code" maxlength="3"
                                class="form-control @error('currency_code') is-invalid @enderror"
                                value="{{ old('currency_code', $sale->currency_code ?: app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency()) }}">
                            @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            @include('shared.accounting.field-label', [
                                'label' => 'Source',
                                'tooltip' => 'Sumber asal transaksi, misalnya input manual, POS, atau integrasi lain. Berguna untuk pelacakan proses penjualan.',
                            ])
                            <select name="source" class="form-select @error('source') is-invalid @enderror">
                                @foreach($sourceOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('source', $sale->source) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <input type="hidden" name="currency_code" value="{{ old('currency_code', $sale->currency_code ?: app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency()) }}">
                        <input type="hidden" name="source" value="{{ old('source', $sale->source ?: \App\Modules\Sales\Models\Sale::SOURCE_MANUAL) }}">
                    @endif

                    <div class="col-md-6">
                        @include('shared.accounting.field-label', [
                            'label' => 'Payment Status',
                            'tooltip' => 'Menunjukkan apakah penjualan sudah dibayar penuh, sebagian, atau belum dibayar. Status ini membantu pemantauan piutang.',
                        ])
                        <select name="payment_status" class="form-select @error('payment_status') is-invalid @enderror">
                            @foreach($paymentStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_status', $sale->payment_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($isAdvancedMode)
                        <div class="col-12">
                            @include('shared.accounting.field-label', [
                                'label' => 'External Reference',
                                'tooltip' => 'Nomor referensi dari sistem lain seperti POS, marketplace, atau API. Boleh dikosongkan jika transaksi dibuat langsung di aplikasi ini.',
                            ])
                            <input type="text" name="external_reference"
                                class="form-control @error('external_reference') is-invalid @enderror"
                                placeholder="POS, API, or online order ref"
                                value="{{ old('external_reference', $sale->external_reference) }}">
                            @error('external_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-12">
                        @include('shared.accounting.field-label', [
                            'label' => 'Internal Notes',
                            'tooltip' => 'Catatan internal untuk tim. Tidak ditujukan untuk customer dan tidak perlu ikut dicetak di invoice.',
                        ])
                        <textarea name="notes" rows="3"
                            class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $sale->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        @include('shared.accounting.field-label', [
                            'label' => 'Customer Note',
                            'tooltip' => 'Catatan yang boleh dibaca customer, misalnya instruksi pengiriman, pesan invoice, atau kesepakatan ringkas.',
                        ])
                        <textarea name="customer_note" rows="2"
                            class="form-control @error('customer_note') is-invalid @enderror">{{ old('customer_note', $sale->customer_note) }}</textarea>
                        @error('customer_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        @include('shared.accounting.field-label', [
                            'label' => 'Attachment',
                            'tooltip' => 'Upload dokumen pendukung transaksi seperti PO customer, form order, atau file pendukung lain.',
                        ])
                        <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                        @if($sale->attachment_path)
                            <div class="form-hint">Attachment saat ini: <a href="{{ asset('storage/'.$sale->attachment_path) }}" target="_blank" rel="noopener">lihat file</a></div>
                        @endif
                        @error('attachment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($isAdvancedMode)
                        <div class="col-md-6">
                            @include('shared.accounting.field-label', [
                                'label' => 'Header Discount',
                                'tooltip' => 'Diskon nominal untuk seluruh transaksi, di luar diskon per item. Cocok untuk potongan invoice atau negosiasi final.',
                            ])
                            <input type="number" min="0" step="0.01" name="header_discount_total"
                                class="form-control @error('header_discount_total') is-invalid @enderror"
                                value="{{ $headerDiscountTotal }}">
                            @error('header_discount_total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            @include('shared.accounting.field-label', [
                                'label' => 'Header Tax',
                                'tooltip' => 'Pajak nominal untuk seluruh transaksi, di luar pajak per item. Isi jika pajak dihitung di level invoice.',
                            ])
                            <input type="number" min="0" step="0.01" name="header_tax_total"
                                class="form-control @error('header_tax_total') is-invalid @enderror"
                                value="{{ $headerTaxTotal }}">
                            @error('header_tax_total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <input type="hidden" name="header_discount_total" value="{{ $headerDiscountTotal }}">
                        <input type="hidden" name="header_tax_total" value="{{ $headerTaxTotal }}">
                    @endif
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
                                        @include('shared.accounting.field-label', [
                                            'label' => 'Unit Price',
                                            'tooltip' => 'Harga jual per unit item ini. Nilai default diambil dari master product dan masih bisa disesuaikan bila perlu.',
                                        ])
                                        <input type="number" min="0" step="0.01"
                                            name="items[{{ $index }}][unit_price]" class="form-control"
                                            value="{{ $item['unit_price'] }}" data-item-price>
                                    </div>
                                    @if($isAdvancedMode)
                                        <div class="col-md-1">
                                            @include('shared.accounting.field-label', [
                                                'label' => 'Disc.',
                                                'tooltip' => 'Diskon nominal untuk item ini. Isi 0 jika tidak ada diskon khusus.',
                                            ])
                                            <input type="number" min="0" step="0.01"
                                                name="items[{{ $index }}][discount_total]" class="form-control"
                                                value="{{ $item['discount_total'] }}">
                                        </div>
                                        <div class="col-md-1">
                                            @include('shared.accounting.field-label', [
                                                'label' => 'Tax',
                                                'tooltip' => 'Nilai pajak nominal untuk item ini. Isi hanya jika transaksi item memang dikenakan pajak.',
                                            ])
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
                                            title="Remove item" data-remove-sale-item>
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                    @if($isAdvancedMode)
                                        <div class="col-12">
                                            @include('shared.accounting.field-label', [
                                                'label' => 'Internal Notes',
                                                'tooltip' => 'Catatan internal untuk item ini, misalnya permintaan khusus atau keterangan tambahan. Boleh dikosongkan.',
                                            ])
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
                    <button type="button" class="btn btn-icon btn-sm btn-outline-danger" title="Remove item" data-remove-sale-item>
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
