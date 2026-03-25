<?php

namespace App\Modules\WhatsAppWeb\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'string', 'max:100'],
            'limit'     => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
