<?php

namespace App\Modules\WhatsAppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWATemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:150'],
            'meta_name'    => ['nullable', 'string', 'max:150', 'regex:/^[a-z_]+$/'],
            'language'     => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'category'     => ['required', 'in:utility,marketing,authentication'],
            'instance_id'  => ['required', 'integer'],
            'body'         => ['required', 'string', 'max:1024'],
            'status'       => ['required', 'in:draft,pending,approved,rejected'],
            'header_type'  => ['nullable', 'in:none,text,image,document,video'],
            'header_text'  => ['nullable', 'string', 'max:60'],
            'header_media_url'  => ['nullable', 'string', 'max:2048'],
            'header_media_file' => ['nullable', 'file', 'max:20480'],
            'footer_text'  => ['nullable', 'string', 'max:60'],

            'variable_mappings'                  => ['nullable', 'array'],
            'variable_mappings.*.source_type'    => ['nullable', 'in:text,contact_field,sender_field'],
            'variable_mappings.*.text_value'     => ['nullable', 'string', 'max:500'],
            'variable_mappings.*.contact_field'  => ['nullable', 'string', 'max:50'],
            'variable_mappings.*.sender_field'   => ['nullable', 'string', 'max:50'],
            'variable_mappings.*.fallback_value' => ['nullable', 'string', 'max:500'],

            'buttons'               => ['nullable', 'array'],
            'buttons.*.type'        => ['nullable', 'in:quick_reply,url,phone_number,copy_code'],
            'buttons.*.label'       => ['nullable', 'string', 'max:25'],
            'buttons.*.url'         => ['nullable', 'string', 'max:2000'],
            'buttons.*.phone_number'=> ['nullable', 'regex:/^\+?[1-9]\d{6,14}$/'],
            'buttons.*.example'     => ['nullable', 'string', 'max:255'],

            // Legacy form compatibility
            'qr_label'        => ['array'],
            'qr_label.*'      => ['nullable', 'string', 'max:25'],
            'cta_url_label'   => ['nullable', 'string', 'max:25'],
            'cta_url_value'   => ['nullable', 'string', 'max:2000'],
            'cta_phone_label' => ['nullable', 'string', 'max:25'],
            'cta_phone_value' => ['nullable', 'regex:/^\+?[1-9]\d{6,14}$/'],
            'button_mode'     => ['nullable', 'in:none,quick_reply,cta'],
        ];
    }
}
