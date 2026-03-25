<?php

namespace App\Modules\Chatbot\Http\Requests;

class UpdateChatbotAccountRequest extends StoreChatbotAccountRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'api_key' => ['nullable', 'string'],
        ]);
    }
}
