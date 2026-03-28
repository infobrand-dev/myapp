<?php

namespace App\Modules\Chatbot\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatbotPlaygroundSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chatbot_account_id' => [
                'required',
                'integer',
                Rule::exists('chatbot_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId())),
            ],
            'session_id'         => ['nullable', 'integer'],
            'message'            => ['required', 'string', 'max:4000'],
        ];
    }
}
