<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:admin,manager,cashier,waiter'],
            'per_page' => [
                'nullable',
                'integer',
                Rule::in(config('pagination.allowed_per_page')),
            ],
        ];
    }
}
