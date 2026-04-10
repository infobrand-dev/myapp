<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinalizeSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales.finalize') : false;
    }

    public function rules(): array
    {
        return [
            'payment_status' => ['nullable', Rule::in([
                Sale::PAYMENT_UNPAID,
                Sale::PAYMENT_PARTIAL,
                Sale::PAYMENT_PAID,
                Sale::PAYMENT_REFUNDED,
            ])],
            'due_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
