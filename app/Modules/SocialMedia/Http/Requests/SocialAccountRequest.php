<?php

namespace App\Modules\SocialMedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'in:instagram,facebook'],
            'page_id' => ['nullable', 'string', 'max:255'],
            'ig_business_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'auto_reply' => ['sometimes', 'boolean'],
            'chatbot_account_id' => ['nullable', 'exists:chatbot_accounts,id'],
        ];
    }
}
