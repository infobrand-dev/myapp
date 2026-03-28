<?php

namespace App\Modules\Chatbot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatbotAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $automationMode = strtolower((string) $this->input('automation_mode', 'ai_first'));

        return [
            'name'                    => ['required', 'string', 'max:255'],
            'provider'                => ['required', 'in:openai'],
            'model'                   => ['nullable', 'string', 'max:255'],
            'automation_mode'         => ['required', 'in:rule_only,ai_assisted,ai_first'],
            'system_prompt'           => ['nullable', 'string', 'max:10000'],
            'focus_scope'             => ['nullable', 'string', 'max:10000'],
            'response_style'          => ['required', 'in:concise,balanced,detailed'],
            'operation_mode'          => ['required', 'in:ai_only,ai_then_human'],
            'api_key'                 => [$automationMode === 'rule_only' ? 'nullable' : 'required', 'string'],
            'status'                  => ['required', 'in:active,inactive'],
            'mirror_to_conversations' => ['sometimes', 'boolean'],
            'rag_enabled'             => ['sometimes', 'boolean'],
            'rag_top_k'               => ['nullable', 'integer', 'min:1', 'max:8'],
            'metadata'                => ['nullable'],
        ];
    }
}
