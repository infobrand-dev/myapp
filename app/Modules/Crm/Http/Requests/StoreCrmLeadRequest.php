<?php

namespace App\Modules\Crm\Http\Requests;

use App\Modules\Crm\Support\CrmStageCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrmLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId())),
            ],
            'owner_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId())),
            ],
            'title' => ['required', 'string', 'max:255'],
            'stage' => ['required', Rule::in(array_keys(CrmStageCatalog::options()))],
            'priority' => ['nullable', Rule::in(array_keys(CrmStageCatalog::priorities()))],
            'lead_source' => ['nullable', 'string', 'max:100'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_follow_up_at' => ['nullable', 'date'],
            'last_contacted_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'labels' => ['nullable', 'string', 'max:1000'],
            'is_archived' => ['nullable', 'boolean'],
        ];
    }
}
