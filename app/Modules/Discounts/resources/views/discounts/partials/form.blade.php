@php
    $isEdit = $discount->exists;
    $targets = old('targets', $discount->targets->map(fn ($target) => [
        'target_type' => $target->target_type,
        'target_id' => $target->target_id,
        'target_code' => $target->target_code,
        'operator' => $target->operator,
    ])->values()->all());
    $conditions = old('conditions', $discount->conditions->map(fn ($condition) => [
        'condition_type' => $condition->condition_type,
        'operator' => $condition->operator,
        'value_type' => $condition->value_type,
        'value' => $condition->value,
        'secondary_value' => $condition->secondary_value,
    ])->values()->all());
    $vouchers = old('vouchers', $discount->vouchers->map(fn ($voucher) => [
        'id' => $voucher->id,
        'code' => $voucher->code,
        'description' => $voucher->description,
        'starts_at' => optional($voucher->starts_at)->format('Y-m-d\TH:i'),
        'ends_at' => optional($voucher->ends_at)->format('Y-m-d\TH:i'),
        'usage_limit' => $voucher->usage_limit,
        'usage_limit_per_customer' => $voucher->usage_limit_per_customer,
        'is_active' => $voucher->is_active,
    ])->values()->all());
    $rulePayload = old('rule_payload', $discount->rule_payload ?? []);
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $submitRoute }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Discount Master</h3></div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label">Internal name</label><input type="text" name="internal_name" class="form-control" value="{{ old('internal_name', $discount->internal_name) }}" required></div>
                    <div class="col-md-6"><label class="form-label">Public label</label><input type="text" name="public_label" class="form-control" value="{{ old('public_label', $discount->public_label) }}"></div>
                    <div class="col-md-4"><label class="form-label">Discount code</label><input type="text" name="code" class="form-control" value="{{ old('code', $discount->code) }}"></div>
                    <div class="col-md-4"><label class="form-label">Type</label><select name="discount_type" class="form-select">
                        @foreach(['fixed_amount' => 'Fixed Amount', 'percentage' => 'Percentage', 'buy_x_get_y' => 'Buy X Get Y', 'free_item' => 'Free Item', 'bundle' => 'Bundle'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('discount_type', $discount->discount_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select></div>
                    <div class="col-md-4"><label class="form-label">Scope</label><select name="application_scope" class="form-select">
                        <option value="item" @selected(old('application_scope', $discount->application_scope) === 'item')>Item</option>
                        <option value="invoice" @selected(old('application_scope', $discount->application_scope) === 'invoice')>Invoice</option>
                    </select></div>
                    <div class="col-md-3"><label class="form-label">Priority</label><input type="number" min="1" name="priority" class="form-control" value="{{ old('priority', $discount->priority) }}"></div>
                    <div class="col-md-3"><label class="form-label">Sequence</label><input type="number" min="1" name="sequence" class="form-control" value="{{ old('sequence', $discount->sequence) }}"></div>
                    <div class="col-md-3"><label class="form-label">Start date</label><input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', optional($discount->starts_at)->format('Y-m-d\TH:i')) }}"></div>
                    <div class="col-md-3"><label class="form-label">End date</label><input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', optional($discount->ends_at)->format('Y-m-d\TH:i')) }}"></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $discount->description) }}</textarea></div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Rule Payload</h3></div>
                <div class="card-body row g-3">
                    <div class="col-md-3"><label class="form-label">Percentage</label><input type="number" step="0.01" min="0" max="100" name="rule_payload[percentage]" class="form-control" value="{{ $rulePayload['percentage'] ?? '' }}"></div>
                    <div class="col-md-3"><label class="form-label">Fixed amount</label><input type="number" step="0.01" min="0" name="rule_payload[amount]" class="form-control" value="{{ $rulePayload['amount'] ?? '' }}"></div>
                    <div class="col-md-2"><label class="form-label">Buy qty</label><input type="number" min="1" name="rule_payload[buy_quantity]" class="form-control" value="{{ $rulePayload['buy_quantity'] ?? '' }}"></div>
                    <div class="col-md-2"><label class="form-label">Get qty</label><input type="number" min="1" name="rule_payload[get_quantity]" class="form-control" value="{{ $rulePayload['get_quantity'] ?? '' }}"></div>
                    <div class="col-md-2"><label class="form-label">Free qty</label><input type="number" min="1" name="rule_payload[free_quantity]" class="form-control" value="{{ $rulePayload['free_quantity'] ?? '' }}"></div>
                    <div class="col-md-4"><label class="form-label">Bundle min qty</label><input type="number" min="1" name="rule_payload[minimum_bundle_quantity]" class="form-control" value="{{ $rulePayload['minimum_bundle_quantity'] ?? '' }}"></div>
                    <div class="col-md-4"><label class="form-label">Bundle mode</label><select name="rule_payload[bundle_discount_mode]" class="form-select">
                        <option value="">Pilih mode</option>
                        <option value="percentage" @selected(($rulePayload['bundle_discount_mode'] ?? '') === 'percentage')>Percentage</option>
                        <option value="fixed_amount" @selected(($rulePayload['bundle_discount_mode'] ?? '') === 'fixed_amount')>Fixed Amount</option>
                    </select></div>
                    <div class="col-md-4"><label class="form-label">Max discount amount</label><input type="number" step="0.01" min="0" name="max_discount_amount" class="form-control" value="{{ old('max_discount_amount', $discount->max_discount_amount) }}"></div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Discount Targets</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-target-row">Tambah target</button>
                </div>
                <div class="card-body" id="target-rows">
                    @foreach($targets ?: [['target_type' => 'all_products', 'target_id' => '', 'target_code' => '', 'operator' => 'include']] as $index => $target)
                        <div class="row g-2 align-items-end mb-2 target-row">
                            <div class="col-md-3">
                                <label class="form-label">Target type</label>
                                <select name="targets[{{ $index }}][target_type]" class="form-select">
                                    @foreach(['all_products', 'product', 'variant', 'category', 'brand', 'customer', 'customer_group', 'outlet', 'sales_channel'] as $type)
                                        <option value="{{ $type }}" @selected(($target['target_type'] ?? '') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label">Target ID</label><input type="number" name="targets[{{ $index }}][target_id]" class="form-control" value="{{ $target['target_id'] ?? '' }}"></div>
                            <div class="col-md-3"><label class="form-label">Target code</label><input type="text" name="targets[{{ $index }}][target_code]" class="form-control" value="{{ $target['target_code'] ?? '' }}"></div>
                            <div class="col-md-2"><label class="form-label">Operator</label><select name="targets[{{ $index }}][operator]" class="form-select"><option value="include" @selected(($target['operator'] ?? '') === 'include')>Include</option><option value="exclude" @selected(($target['operator'] ?? '') === 'exclude')>Exclude</option></select></div>
                            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-target-row"><i class="ti ti-x"></i></button></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Discount Conditions</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-condition-row">Tambah condition</button>
                </div>
                <div class="card-body" id="condition-rows">
                    @foreach($conditions ?: [['condition_type' => 'minimum_subtotal', 'operator' => '>=', 'value_type' => 'numeric', 'value' => '', 'secondary_value' => '']] as $index => $condition)
                        <div class="row g-2 align-items-end mb-2 condition-row">
                            <div class="col-md-3"><label class="form-label">Condition</label><select name="conditions[{{ $index }}][condition_type]" class="form-select">
                                @foreach(['minimum_quantity', 'minimum_subtotal', 'minimum_transaction_amount', 'eligible_subtotal', 'buy_specific_product', 'specific_customer', 'date_range', 'day_of_week', 'time_range'] as $type)
                                    <option value="{{ $type }}" @selected(($condition['condition_type'] ?? '') === $type)>{{ $type }}</option>
                                @endforeach
                            </select></div>
                            <div class="col-md-2"><label class="form-label">Operator</label><input type="text" name="conditions[{{ $index }}][operator]" class="form-control" value="{{ $condition['operator'] ?? '>=' }}"></div>
                            <div class="col-md-2"><label class="form-label">Value type</label><input type="text" name="conditions[{{ $index }}][value_type]" class="form-control" value="{{ $condition['value_type'] ?? 'string' }}"></div>
                            <div class="col-md-2"><label class="form-label">Value</label><input type="text" name="conditions[{{ $index }}][value]" class="form-control" value="{{ $condition['value'] ?? '' }}"></div>
                            <div class="col-md-2"><label class="form-label">2nd value</label><input type="number" step="0.01" name="conditions[{{ $index }}][secondary_value]" class="form-control" value="{{ $condition['secondary_value'] ?? '' }}"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-condition-row"><i class="ti ti-x"></i></button></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Voucher / Promo Code</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-voucher-row">Tambah voucher</button>
                </div>
                <div class="card-body" id="voucher-rows">
                    @foreach($vouchers ?: [['code' => '', 'description' => '', 'starts_at' => '', 'ends_at' => '', 'usage_limit' => '', 'usage_limit_per_customer' => '', 'is_active' => true]] as $index => $voucher)
                        <div class="border rounded p-3 mb-3 voucher-row">
                            <input type="hidden" name="vouchers[{{ $index }}][id]" value="{{ $voucher['id'] ?? '' }}">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label">Code</label><input type="text" name="vouchers[{{ $index }}][code]" class="form-control" value="{{ $voucher['code'] ?? '' }}"></div>
                                <div class="col-md-3"><label class="form-label">Description</label><input type="text" name="vouchers[{{ $index }}][description]" class="form-control" value="{{ $voucher['description'] ?? '' }}"></div>
                                <div class="col-md-2"><label class="form-label">Start</label><input type="datetime-local" name="vouchers[{{ $index }}][starts_at]" class="form-control" value="{{ $voucher['starts_at'] ?? '' }}"></div>
                                <div class="col-md-2"><label class="form-label">End</label><input type="datetime-local" name="vouchers[{{ $index }}][ends_at]" class="form-control" value="{{ $voucher['ends_at'] ?? '' }}"></div>
                                <div class="col-md-1"><label class="form-label">Limit</label><input type="number" min="1" name="vouchers[{{ $index }}][usage_limit]" class="form-control" value="{{ $voucher['usage_limit'] ?? '' }}"></div>
                                <div class="col-md-1"><label class="form-label">Per cust</label><input type="number" min="1" name="vouchers[{{ $index }}][usage_limit_per_customer]" class="form-control" value="{{ $voucher['usage_limit_per_customer'] ?? '' }}"></div>
                                <div class="col-md-2"><div class="form-check mt-4"><input type="hidden" name="vouchers[{{ $index }}][is_active]" value="0"><input type="checkbox" class="form-check-input" name="vouchers[{{ $index }}][is_active]" value="1" @checked((bool) ($voucher['is_active'] ?? true))><label class="form-check-label">Active</label></div></div>
                                <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100 remove-voucher-row"><i class="ti ti-trash"></i></button></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Behavior & Limits</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12"><div class="form-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked((bool) old('is_active', $discount->is_active))><label class="form-check-label">Active</label></div></div>
                    <div class="col-12"><div class="form-check"><input type="hidden" name="is_voucher_required" value="0"><input type="checkbox" class="form-check-input" name="is_voucher_required" value="1" @checked((bool) old('is_voucher_required', $discount->is_voucher_required))><label class="form-check-label">Voucher required</label></div></div>
                    <div class="col-12"><div class="form-check"><input type="hidden" name="is_manual_only" value="0"><input type="checkbox" class="form-check-input" name="is_manual_only" value="1" @checked((bool) old('is_manual_only', $discount->is_manual_only))><label class="form-check-label">Manual apply only</label></div></div>
                    <div class="col-12"><div class="form-check"><input type="hidden" name="is_override_allowed" value="0"><input type="checkbox" class="form-check-input" name="is_override_allowed" value="1" @checked((bool) old('is_override_allowed', $discount->is_override_allowed))><label class="form-check-label">Allow override</label></div></div>
                    <div class="col-md-6"><label class="form-label">Stack mode</label><select name="stack_mode" class="form-select"><option value="stackable" @selected(old('stack_mode', $discount->stack_mode) === 'stackable')>Stackable</option><option value="non_stackable" @selected(old('stack_mode', $discount->stack_mode) === 'non_stackable')>Non-stackable</option></select></div>
                    <div class="col-md-6"><label class="form-label">Combination</label><select name="combination_mode" class="form-select"><option value="combinable" @selected(old('combination_mode', $discount->combination_mode) === 'combinable')>Combinable</option><option value="exclusive" @selected(old('combination_mode', $discount->combination_mode) === 'exclusive')>Exclusive</option></select></div>
                    <div class="col-md-6"><label class="form-label">Usage limit</label><input type="number" min="1" name="usage_limit" class="form-control" value="{{ old('usage_limit', $discount->usage_limit) }}"></div>
                    <div class="col-md-6"><label class="form-label">Per customer</label><input type="number" min="1" name="usage_limit_per_customer" class="form-control" value="{{ old('usage_limit_per_customer', $discount->usage_limit_per_customer) }}"></div>
                    <div class="col-12">
                        <div class="alert alert-secondary mb-0">
                            Products menyimpan master data dan base price. Rule discount, voucher, campaign, dan evaluasi transaksi tetap menjadi source of truth module Discounts.
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Update Discount' : 'Simpan Discount' }}</button>
                <a href="{{ $isEdit ? route('discounts.show', $discount) : route('discounts.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
(() => {
    const reindexRows = (container, rowSelector) => {
        container.querySelectorAll(rowSelector).forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/\[\d+\]/, `[${index}]`);
            });
        });
    };

    const targetRows = document.getElementById('target-rows');
    const conditionRows = document.getElementById('condition-rows');
    const voucherRows = document.getElementById('voucher-rows');

    document.getElementById('add-target-row')?.addEventListener('click', () => {
        const index = targetRows.querySelectorAll('.target-row').length;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-end mb-2 target-row';
        row.innerHTML = `
            <div class="col-md-3"><label class="form-label">Target type</label><select name="targets[${index}][target_type]" class="form-select"><option value="all_products">all_products</option><option value="product">product</option><option value="variant">variant</option><option value="category">category</option><option value="brand">brand</option><option value="customer">customer</option><option value="customer_group">customer_group</option><option value="outlet">outlet</option><option value="sales_channel">sales_channel</option></select></div>
            <div class="col-md-3"><label class="form-label">Target ID</label><input type="number" name="targets[${index}][target_id]" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Target code</label><input type="text" name="targets[${index}][target_code]" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Operator</label><select name="targets[${index}][operator]" class="form-select"><option value="include">Include</option><option value="exclude">Exclude</option></select></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-target-row"><i class="ti ti-x"></i></button></div>
        `;
        targetRows.appendChild(row);
    });

    targetRows?.addEventListener('click', (event) => {
        if (!event.target.closest('.remove-target-row')) return;
        event.target.closest('.target-row')?.remove();
        reindexRows(targetRows, '.target-row');
    });

    document.getElementById('add-condition-row')?.addEventListener('click', () => {
        const index = conditionRows.querySelectorAll('.condition-row').length;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-end mb-2 condition-row';
        row.innerHTML = `
            <div class="col-md-3"><label class="form-label">Condition</label><select name="conditions[${index}][condition_type]" class="form-select"><option value="minimum_quantity">minimum_quantity</option><option value="minimum_subtotal">minimum_subtotal</option><option value="minimum_transaction_amount">minimum_transaction_amount</option><option value="eligible_subtotal">eligible_subtotal</option><option value="buy_specific_product">buy_specific_product</option><option value="specific_customer">specific_customer</option><option value="date_range">date_range</option><option value="day_of_week">day_of_week</option><option value="time_range">time_range</option></select></div>
            <div class="col-md-2"><label class="form-label">Operator</label><input type="text" name="conditions[${index}][operator]" class="form-control" value=">="></div>
            <div class="col-md-2"><label class="form-label">Value type</label><input type="text" name="conditions[${index}][value_type]" class="form-control" value="string"></div>
            <div class="col-md-2"><label class="form-label">Value</label><input type="text" name="conditions[${index}][value]" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">2nd value</label><input type="number" step="0.01" name="conditions[${index}][secondary_value]" class="form-control"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-condition-row"><i class="ti ti-x"></i></button></div>
        `;
        conditionRows.appendChild(row);
    });

    conditionRows?.addEventListener('click', (event) => {
        if (!event.target.closest('.remove-condition-row')) return;
        event.target.closest('.condition-row')?.remove();
        reindexRows(conditionRows, '.condition-row');
    });

    document.getElementById('add-voucher-row')?.addEventListener('click', () => {
        const index = voucherRows.querySelectorAll('.voucher-row').length;
        const row = document.createElement('div');
        row.className = 'border rounded p-3 mb-3 voucher-row';
        row.innerHTML = `
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Code</label><input type="text" name="vouchers[${index}][code]" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Description</label><input type="text" name="vouchers[${index}][description]" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Start</label><input type="datetime-local" name="vouchers[${index}][starts_at]" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">End</label><input type="datetime-local" name="vouchers[${index}][ends_at]" class="form-control"></div>
                <div class="col-md-1"><label class="form-label">Limit</label><input type="number" min="1" name="vouchers[${index}][usage_limit]" class="form-control"></div>
                <div class="col-md-1"><label class="form-label">Per cust</label><input type="number" min="1" name="vouchers[${index}][usage_limit_per_customer]" class="form-control"></div>
                <div class="col-md-2"><div class="form-check mt-4"><input type="hidden" name="vouchers[${index}][is_active]" value="0"><input type="checkbox" class="form-check-input" name="vouchers[${index}][is_active]" value="1" checked><label class="form-check-label">Active</label></div></div>
                <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100 remove-voucher-row"><i class="ti ti-trash"></i></button></div>
            </div>
        `;
        voucherRows.appendChild(row);
    });

    voucherRows?.addEventListener('click', (event) => {
        if (!event.target.closest('.remove-voucher-row')) return;
        event.target.closest('.voucher-row')?.remove();
        reindexRows(voucherRows, '.voucher-row');
    });
})();
</script>
@endpush
