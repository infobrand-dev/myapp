<?php

namespace App\Modules\LiveChat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLiveChatWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:150'],
            'website_name'         => ['nullable', 'string', 'max:150'],
            'welcome_text'         => ['nullable', 'string', 'max:1000'],
            'theme_color'          => ['required', 'string', 'max:20'],
            'launcher_label'       => ['nullable', 'string', 'max:40'],
            'position'             => ['nullable', 'in:left,right'],
            'logo_url'             => ['nullable', 'string', 'max:500'],
            'header_bg_color'      => ['nullable', 'string', 'max:20'],
            'visitor_bubble_color' => ['nullable', 'string', 'max:20'],
            'agent_bubble_color'   => ['nullable', 'string', 'max:20'],
            'allowed_domains'      => ['nullable', 'string'],
            'is_active'            => ['nullable', 'boolean'],
        ];
    }
}
