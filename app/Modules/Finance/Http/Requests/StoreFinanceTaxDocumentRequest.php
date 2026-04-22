<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\FinanceTaxDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinanceTaxDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-tax') : false;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(array_keys(FinanceTaxDocument::documentTypeOptions()))],
            'document_status' => ['required', Rule::in(array_keys(FinanceTaxDocument::documentStatusOptions()))],
            'source_reference' => ['nullable', 'string', 'max:50'],
            'finance_tax_rate_id' => ['nullable', 'integer'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'external_document_number' => ['nullable', 'string', 'max:100'],
            'document_date' => ['required', 'date'],
            'transaction_date' => ['nullable', 'date'],
            'tax_period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'tax_period_year' => ['required', 'integer', 'min:2000', 'max:2999'],
            'taxable_base' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'withheld_amount' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'counterparty_name_snapshot' => ['nullable', 'string', 'max:255'],
            'counterparty_tax_id_snapshot' => ['nullable', 'string', 'max:100'],
            'counterparty_tax_name_snapshot' => ['nullable', 'string', 'max:255'],
            'counterparty_tax_address_snapshot' => ['nullable', 'string'],
            'reference_note' => ['nullable', 'string'],
        ];
    }
}
