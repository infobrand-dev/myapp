<?php

namespace App\Modules\PointOfSale\Http\Requests;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateActiveCartRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user() ? $this->user()->can('pos.use') : false;
    }

    public function rules(): array
    {
        return [
            'contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')->where(fn ($query) => ContactScope::applyVisibilityScope($query))],
            'customer_label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateTenantRelations($validator),
        ];
    }

    private function validateTenantRelations(Validator $validator): void
    {
        $contactId = $this->input('contact_id');
        if ($contactId && !ContactScope::applyVisibilityScope(Contact::query())->find($contactId)) {
            $validator->errors()->add('contact_id', 'Contact tidak tersedia untuk tenant aktif.');
        }
    }
}
