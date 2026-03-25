<?php

namespace App\Modules\Conversations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationContactNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
