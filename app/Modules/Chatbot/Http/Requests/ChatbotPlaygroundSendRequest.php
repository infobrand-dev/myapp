<?php

namespace App\Modules\Chatbot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotPlaygroundSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chatbot_account_id' => ['required', 'integer', 'exists:chatbot_accounts,id'],
            'session_id'         => ['nullable', 'integer'],
            'message'            => ['required', 'string', 'max:4000'],
        ];
    }
}
