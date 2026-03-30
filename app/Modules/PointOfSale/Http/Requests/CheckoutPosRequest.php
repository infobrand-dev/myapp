<?php

namespace App\Modules\PointOfSale\Http\Requests;

use App\Support\CurrencySettingsResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutPosRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            ? $user->can('pos.checkout')
                && $user->can('sales.create')
                && $user->can('sales.finalize')
                && $user->can('payments.create')
            : false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', 'string', Rule::in(['cash', 'bank_transfer', 'debit_card', 'credit_card', 'card', 'ewallet', 'qris', 'manual', 'other'])],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.currency_code' => ['nullable', 'string', 'size:3'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:100'],
            'payments.*.notes' => ['nullable', 'string'],
            'payments.*.payment_date' => ['nullable', 'date'],
            'cash_received_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payments = collect($this->input('payments', []))
            ->filter(function ($payment) {
                return is_array($payment);
            })
            ->map(function (array $payment) {
                $payment['currency_code'] = !empty($payment['currency_code'])
                    ? strtoupper((string) $payment['currency_code'])
                    : app(CurrencySettingsResolver::class)->defaultCurrency();

                return $payment;
            })
            ->values()
            ->all();

        $this->merge([
            'payments' => $payments,
        ]);
    }
}
