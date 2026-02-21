<?php

namespace App\Http\Requests\ActivityLogs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'subject_type' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => [
                'nullable',
                'integer',
                Rule::in(config('pagination.allowed_per_page')),
            ],
        ];
    }
}
