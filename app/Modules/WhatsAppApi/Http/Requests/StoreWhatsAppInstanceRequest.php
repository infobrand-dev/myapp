<?php

namespace App\Modules\WhatsAppApi\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreWhatsAppInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'name'                      => ['required', 'string', 'max:150'],
            'phone_number'              => ['nullable', 'string', 'max:50'],
            'provider'                  => ['required', 'string', 'max:50'],
            'api_base_url'              => ['nullable', 'url', 'max:255'],
            'api_token'                 => ['nullable', 'string', 'max:255'],
            'is_active'                 => ['boolean'],
            'settings'                  => ['nullable'],
            'handoff_ack_message'       => ['nullable', 'string', 'max:2000'],
            'auto_assignment_enabled'   => ['sometimes', 'boolean'],
            'wa_cloud_verify_token'     => ['nullable', 'string', 'max:255'],
            'wa_cloud_app_secret'       => ['nullable', 'string', 'max:255'],
            'wa_cloud_app_id'           => ['nullable', 'string', 'max:255'],
            'auto_reply'                => ['sometimes', 'boolean'],
            'chatbot_account_id'        => $chatbotRule,
            'phone_number_id'           => ['nullable', 'string', 'max:100'],
            'cloud_business_account_id' => ['nullable', 'string', 'max:100'],
            'cloud_token'               => ['nullable', 'string'],
        ];
    }
}
