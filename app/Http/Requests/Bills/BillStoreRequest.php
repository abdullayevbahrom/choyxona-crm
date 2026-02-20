<?php

namespace App\Http\Requests\Bills;

use Illuminate\Foundation\Http\FormRequest;

class BillStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', 'in:cash,card,transfer'],
            'discount_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'prohibits:discount_amount',
            ],
            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'prohibits:discount_percent',
            ],
        ];
    }
}
