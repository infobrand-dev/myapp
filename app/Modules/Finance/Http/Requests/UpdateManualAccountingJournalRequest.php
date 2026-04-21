<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Http\Requests\Concerns\ValidatesManualJournalPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateManualAccountingJournalRequest extends FormRequest
{
    use ValidatesManualJournalPayload;

    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-journal') : false;
    }

    public function rules(): array
    {
        return $this->manualJournalRules();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeManualJournalPayload();
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateManualJournalBalanced($validator),
        ];
    }
}
