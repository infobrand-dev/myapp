<?php

namespace App\Modules\WhatsAppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsAppInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
            'chatbot_account_id'        => ['nullable', 'integer'],
            'phone_number_id'           => ['nullable', 'string', 'max:100'],
            'cloud_business_account_id' => ['nullable', 'string', 'max:100'],
            'cloud_token'               => ['nullable', 'string'],
        ];
    }
}
