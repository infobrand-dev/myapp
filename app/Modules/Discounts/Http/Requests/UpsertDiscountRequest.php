<?php

namespace App\Modules\Discounts\Http\Requests;

use App\Modules\Discounts\Models\Discount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('discount') ? 'discounts.update' : 'discounts.create') ?? false;
    }

    public function rules(): array
    {
        $discountId = $this->route('discount')?->id;

        return [
            'internal_name' => ['required', 'string', 'max:255'],
            'public_label' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('discounts', 'code')->ignore($discountId)],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', Rule::in([
                Discount::TYPE_FIXED_AMOUNT,
                Discount::TYPE_PERCENTAGE,
                Discount::TYPE_BUY_X_GET_Y,
                Discount::TYPE_FREE_ITEM,
                Discount::TYPE_BUNDLE,
            ])],
            'application_scope' => ['required', Rule::in([Discount::SCOPE_INVOICE, Discount::SCOPE_ITEM])],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'sequence' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'is_archived' => ['nullable', 'boolean'],
            'is_voucher_required' => ['nullable', 'boolean'],
            'is_manual_only' => ['nullable', 'boolean'],
            'is_override_allowed' => ['nullable', 'boolean'],
            'stack_mode' => ['required', Rule::in(['stackable', 'non_stackable'])],
            'combination_mode' => ['required', Rule::in(['combinable', 'exclusive'])],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'rule_payload' => ['nullable', 'array'],
            'rule_payload.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rule_payload.amount' => ['nullable', 'numeric', 'min:0'],
            'rule_payload.buy_quantity' => ['nullable', 'integer', 'min:1'],
            'rule_payload.get_quantity' => ['nullable', 'integer', 'min:1'],
            'rule_payload.free_quantity' => ['nullable', 'integer', 'min:1'],
            'rule_payload.minimum_bundle_quantity' => ['nullable', 'integer', 'min:1'],
            'rule_payload.bundle_discount_mode' => ['nullable', Rule::in(['percentage', 'fixed_amount'])],
            'targets' => ['nullable', 'array'],
            'targets.*.target_type' => ['required_with:targets', Rule::in(['all_products', 'product', 'variant', 'category', 'brand', 'customer', 'customer_group', 'outlet', 'sales_channel'])],
            'targets.*.target_id' => ['nullable', 'integer'],
            'targets.*.target_code' => ['nullable', 'string', 'max:100'],
            'targets.*.operator' => ['nullable', Rule::in(['include', 'exclude'])],
            'conditions' => ['nullable', 'array'],
            'conditions.*.condition_type' => ['required_with:conditions', Rule::in(['minimum_quantity', 'minimum_subtotal', 'minimum_transaction_amount', 'eligible_subtotal', 'buy_specific_product', 'specific_customer', 'date_range', 'day_of_week', 'time_range'])],
            'conditions.*.operator' => ['nullable', 'string', 'max:20'],
            'conditions.*.value_type' => ['nullable', 'string', 'max:20'],
            'conditions.*.value' => ['nullable', 'string', 'max:255'],
            'conditions.*.secondary_value' => ['nullable', 'numeric'],
            'conditions.*.payload' => ['nullable', 'array'],
            'vouchers' => ['nullable', 'array'],
            'vouchers.*.id' => ['nullable', 'integer'],
            'vouchers.*.code' => ['nullable', 'string', 'max:100'],
            'vouchers.*.description' => ['nullable', 'string', 'max:255'],
            'vouchers.*.starts_at' => ['nullable', 'date'],
            'vouchers.*.ends_at' => ['nullable', 'date', 'after_or_equal:vouchers.*.starts_at'],
            'vouchers.*.usage_limit' => ['nullable', 'integer', 'min:1'],
            'vouchers.*.usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'vouchers.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
