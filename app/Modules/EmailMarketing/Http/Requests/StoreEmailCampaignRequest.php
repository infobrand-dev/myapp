<?php

namespace App\Modules\EmailMarketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $action = $this->input('action', 'save');
        $subjectRule = $action === 'save'
            ? ['nullable', 'string', 'max:255']
            : ['required', 'string', 'max:255'];

        return [
            'subject'                => $subjectRule,
            'body_html'              => ['required', 'string'],
            'scheduled_at'           => ['nullable', 'date', 'after:now'],
            'filters'                => ['array'],
            'attachments.*'          => ['file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg'],
            'dynamic_template_ids'   => ['array'],
            'dynamic_template_ids.*' => ['integer'],
        ];
    }
}
