<?php

namespace App\Modules\WhatsAppApi\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlastCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = TenantContext::currentId();

        return [
            'name'             => ['required', 'string', 'max:150'],
            'instance_id'      => ['required', Rule::exists('whatsapp_instances', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'template_id'      => ['required', Rule::exists('wa_templates', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'recipient_source' => ['required', 'in:manual,csv,contacts'],
            'recipients_text'  => ['nullable', 'string'],
            'recipients_file'  => ['nullable', 'file', 'max:5120', 'mimes:csv,txt'],
            'filters'          => ['nullable', 'array'],
            'scheduled_at'     => ['nullable', 'date'],
            'delay_ms'         => ['nullable', 'integer', 'min:0', 'max:5000'],
            'action'           => ['nullable', 'in:draft,send_now,schedule'],
        ];
    }
}
