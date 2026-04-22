<?php

namespace App\Modules\Payments\Http\Requests;

use App\Modules\Payments\Models\PaymentMethod;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('payments.manage_methods') : false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:payment_methods,code'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in([
                PaymentMethod::TYPE_CASH,
                PaymentMethod::TYPE_BANK_TRANSFER,
                PaymentMethod::TYPE_DEBIT_CARD,
                PaymentMethod::TYPE_CREDIT_CARD,
                PaymentMethod::TYPE_EWALLET,
                PaymentMethod::TYPE_QRIS,
                PaymentMethod::TYPE_MANUAL,
            ])],
            'finance_account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(function ($query) {
                $query->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId());
            })],
            'requires_reference' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
