<?php

namespace App\Modules\Payments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('payments.create') : false;
    }

    public function rules(): array
    {
        $receivedByRules = ['nullable', 'integer', 'exists:users,id'];

        if (!$this->user() || !$this->user()->can('payments.assign_receiver')) {
            $receivedByRules = ['prohibited'];
        }

        return [
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'paid_at' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:30'],
            'channel' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'received_by' => $receivedByRules,
            'outlet_id' => ['nullable', 'integer', 'min:1'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.payable_type' => ['required', Rule::in(['sale', 'sale_return', 'purchase'])],
            'allocations.*.payable_id' => ['required', 'integer', 'min:1'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $allocations = collect($this->input('allocations', []))
            ->filter(fn ($allocation) => is_array($allocation))
            ->map(function (array $allocation) {
                return [
                    'payable_type' => strtolower((string) ($allocation['payable_type'] ?? 'sale')),
                    'payable_id' => $allocation['payable_id'] ?? null,
                    'amount' => $allocation['amount'] ?? null,
                ];
            })
            ->values()
            ->all();

        if (empty($allocations) && $this->filled('sale_id')) {
            $allocations[] = [
                'payable_type' => 'sale',
                'payable_id' => $this->input('sale_id'),
                'amount' => $this->input('amount'),
            ];
        }

        if (empty($allocations) && $this->filled('purchase_id')) {
            $allocations[] = [
                'payable_type' => 'purchase',
                'payable_id' => $this->input('purchase_id'),
                'amount' => $this->input('amount'),
            ];
        }

        $this->merge([
            'currency_code' => strtoupper((string) ($this->input('currency_code') ?: 'IDR')),
            'source' => strtolower((string) ($this->input('source') ?: 'backoffice')),
            'allocations' => $allocations,
        ]);
    }
}
