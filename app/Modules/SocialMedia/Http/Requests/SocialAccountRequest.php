<?php

namespace App\Modules\SocialMedia\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super-admin') ?? false;
    }

    public function rules(): array
    {
        $chatbotRule = ['nullable'];
        if (class_exists(\App\Modules\Chatbot\Models\ChatbotAccount::class) && Schema::hasTable('chatbot_accounts')) {
            $hasAccessScope = Schema::hasColumn('chatbot_accounts', 'access_scope');
            $chatbotRule[] = Rule::exists('chatbot_accounts', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('status', 'active')
                ->when($hasAccessScope, fn ($builder) => $builder->where('access_scope', 'public')));
        } else {
            $chatbotRule[] = 'integer';
        }

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'auto_reply' => ['sometimes', 'boolean'],
            'chatbot_account_id' => $chatbotRule,
        ];
    }
}
