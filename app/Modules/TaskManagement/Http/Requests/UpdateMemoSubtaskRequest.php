<?php

namespace App\Modules\TaskManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemoSubtaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'    => ['sometimes', 'string', 'max:255'],
            'status'   => ['required', 'in:pending,in_progress,done'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
