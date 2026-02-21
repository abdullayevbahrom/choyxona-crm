<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => [
                'nullable',
                'integer',
                Rule::in(config('pagination.allowed_per_page')),
            ],
        ];
    }
}
