<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class OrderCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "room" => ["required", "integer", "exists:rooms,id"],
            "type" => ["nullable", "in:food,drink,bread,salad,sauce"],
            "q" => ["nullable", "string", "max:200"],
        ];
    }
}
