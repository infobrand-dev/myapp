<?php

namespace App\Modules\WhatsAppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RetryFailedMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instance_id' => ['nullable', 'integer'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
        ];
    }
}
