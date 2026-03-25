<?php

namespace App\Modules\WhatsAppWeb\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message'   => ['required', 'string'],
            'client_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
