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
        $behaviorMode = strtolower((string) $this->input('behavior_mode', 'ai_then_human'));
        $aiSource = strtolower((string) $this->input('ai_source', 'managed'));
        $requiresApiKey = $behaviorMode !== 'rule_only' && $aiSource === 'byo';

        return [
            'name'                    => ['required', 'string', 'max:255'],
            'access_scope'            => ['required', 'in:public,private'],
            'ai_source'               => ['required', 'in:managed,byo'],
            'provider'                => ['required', 'in:openai,anthropic,groq'],
            'model'                   => ['nullable', 'string', 'max:255'],
            'behavior_mode'           => ['required', 'in:rule_only,ai_only,ai_then_human'],
            'automation_mode'         => ['nullable', 'in:rule_only,ai_assisted,ai_first'],
            'system_prompt'           => ['nullable', 'string', 'max:10000'],
            'focus_scope'             => ['nullable', 'string', 'max:10000'],
            'response_style'          => ['required', 'in:concise,balanced,detailed'],
            'operation_mode'          => ['nullable', 'in:ai_only,ai_then_human'],
            'api_key'                 => [$requiresApiKey ? 'required' : 'nullable', 'string'],
            'status'                  => ['required', 'in:active,inactive'],
            'mirror_to_conversations' => ['sometimes', 'boolean'],
            'rag_enabled'             => ['sometimes', 'boolean'],
            'rag_top_k'               => ['nullable', 'integer', 'min:1', 'max:8'],
            'auto_reply_enabled'      => ['sometimes', 'boolean'],
            'allowed_channels'        => ['nullable', 'array'],
            'allowed_channels.*'      => ['string', 'in:wa_api,wa_web,social_dm'],
            'allow_interactive_buttons' => ['sometimes', 'boolean'],
            'human_handoff_ack_enabled' => ['sometimes', 'boolean'],
            'minimum_context_score'   => ['nullable', 'numeric', 'min:1', 'max:30'],
            'reply_cooldown_seconds'  => ['nullable', 'integer', 'min:0', 'max:300'],
            'max_bot_replies_per_conversation' => ['nullable', 'integer', 'min:0', 'max:100'],
            'metadata'                => ['nullable'],
        ];
    }
}
