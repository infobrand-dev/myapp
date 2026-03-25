<?php

namespace App\Modules\Conversations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendConversationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->input('message_type', 'text')) {
            'template' => [
                'template_id'    => ['required', 'integer'],
                'template_params'    => ['array'],
                'template_params.*'  => ['nullable', 'string', 'max:250'],
            ],
            'media' => [
                'media_file' => ['required', 'file', 'max:20480'],
                'body'       => ['nullable', 'string', 'max:1000'],
            ],
            default => [
                'body' => ['required', 'string'],
            ],
        };
    }
}
