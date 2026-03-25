<?php

namespace App\Modules\Midtrans\Http\Requests;

use App\Modules\Midtrans\Models\MidtransSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMidtransSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'environment'        => ['required', 'in:sandbox,production'],
            'server_key'         => ['nullable', 'string', 'max:255'],
            'client_key'         => ['nullable', 'string', 'max:255'],
            'merchant_id'        => ['nullable', 'string', 'max:50'],
            'is_active'          => ['boolean'],
            'enabled_payments'   => ['nullable', 'array'],
            'enabled_payments.*' => ['string', Rule::in(array_keys(MidtransSetting::AVAILABLE_PAYMENT_METHODS))],
        ];
    }
}
