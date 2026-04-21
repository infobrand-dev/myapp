<?php

namespace App\Modules\Contacts\Http\Requests;

use App\Modules\Contacts\Support\ContactScope;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['company', 'individual'])],
            'scope' => ['required', Rule::in(ContactScope::visibleLevels())],
            'parent_contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'vat' => ['nullable', 'string', 'max:100'],
            'tax_name' => ['nullable', 'string', 'max:255'],
            'company_registry' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:150'],
            'payment_term_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'contact_person_name' => ['nullable', 'string', 'max:255'],
            'contact_person_phone' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:255'],
            'street2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', 'string', 'max:150'],
            'zip' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:150'],
            'billing_address' => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'tax_address' => ['nullable', 'string'],
            'tax_is_pkp' => ['nullable', 'boolean'],
            'tags_input' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
