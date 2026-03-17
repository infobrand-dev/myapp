<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Models\Sale;
use Illuminate\Validation\Rule;

class FinalizeChannelSaleRequest extends FinalizeSaleRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user || !$user->can('sales.finalize')) {
            return false;
        }

        if (!empty($this->input('payments')) && !$user->can('payments.create')) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['payments'] = ['nullable', 'array'];
        $rules['payments.*.payment_method'] = ['required_with:payments', Rule::in([
            Sale::PAYMENT_METHOD_CASH,
            Sale::PAYMENT_METHOD_BANK_TRANSFER,
            Sale::PAYMENT_METHOD_CARD,
            Sale::PAYMENT_METHOD_EWALLET,
            Sale::PAYMENT_METHOD_QRIS,
            Sale::PAYMENT_METHOD_OTHER,
        ])];
        $rules['payments.*.amount'] = ['required_with:payments', 'numeric', 'gt:0'];
        $rules['payments.*.currency_code'] = ['nullable', 'string', 'size:3'];
        $rules['payments.*.payment_date'] = ['nullable', 'date'];
        $rules['payments.*.reference_number'] = ['nullable', 'string', 'max:100'];
        $rules['payments.*.notes'] = ['nullable', 'string'];

        return $rules;
    }
}
