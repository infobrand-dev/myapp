<?php

namespace App\Modules\SocialMedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InboundSocialWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'               => ['required', 'string'],
            'platform'            => ['required', 'in:instagram,facebook'],
            'contact_id'          => ['required', 'string'],
            'contact_name'        => ['nullable', 'string'],
            'message'             => ['required', 'string'],
            'external_message_id' => ['nullable', 'string'],
            'direction'           => ['nullable', 'in:in,out'],
            'account_id'          => ['nullable', 'integer'],
        ];
    }
}
