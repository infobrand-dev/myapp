<?php

namespace App\Modules\Midtrans\Http\Requests;

use App\Modules\Midtrans\Models\MidtransTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSnapTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payable_type'   => ['required', 'string', Rule::in(MidtransTransaction::ALLOWED_PAYABLE_TYPES)],
            'payable_id'     => ['required', 'integer'],
            'amount'         => ['nullable', 'numeric', 'min:1000'],
            'customer_name'  => ['nullable', 'string', 'max:150'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'description'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'payable_type.in' => 'Jenis pembayaran tidak dikenali.',
        ];
    }
}
