<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class MenuIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'in:food,drink,bread,salad,sauce'],
            'q' => ['nullable', 'string', 'max:200'],
        ];
    }
}
