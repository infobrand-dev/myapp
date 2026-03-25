<?php

namespace App\Modules\Shortlink\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShortlinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $primaryCode = $this->route('shortlink')?->primaryCode;
        $primaryId = $primaryCode ? $primaryCode->id : null;

        return [
            'title'           => ['nullable', 'string', 'max:255'],
            'destination_url' => ['required', 'url'],
            'code'            => ['required', 'alpha_dash', Rule::unique('shortlink_codes', 'code')->ignore($primaryId)],
            'utm_source'      => ['nullable', 'string', 'max:191'],
            'utm_medium'      => ['nullable', 'string', 'max:191'],
            'utm_campaign'    => ['nullable', 'string', 'max:191'],
            'utm_term'        => ['nullable', 'string', 'max:191'],
            'utm_content'     => ['nullable', 'string', 'max:191'],
            'is_active'       => ['sometimes', 'boolean'],
        ];
    }
}
