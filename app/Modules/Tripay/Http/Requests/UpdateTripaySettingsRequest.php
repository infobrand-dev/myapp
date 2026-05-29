<?php

namespace App\Modules\Tripay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'environment' => ['required', 'in:sandbox,production'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'private_key' => ['nullable', 'string', 'max:255'],
            'merchant_code' => ['nullable', 'string', 'max:100'],
            'callback_signature_key' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
