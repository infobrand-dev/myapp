<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\FinanceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinanceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('finance.manage-categories') : false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'transaction_type' => ['required', Rule::in([
                FinanceCategory::TYPE_CASH_IN,
                FinanceCategory::TYPE_CASH_OUT,
                FinanceCategory::TYPE_EXPENSE,
            ])],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
