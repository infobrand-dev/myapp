<?php

namespace App\Modules\EmailMarketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailAttachmentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'filename'    => ['required', 'string', 'max:255'],
            'mime'        => ['required', 'string', 'max:100'],
            'html'        => ['required', 'string'],
            'paper_size'  => ['required', 'in:A4,A4-landscape,Letter,Letter-landscape'],
        ];
    }
}
