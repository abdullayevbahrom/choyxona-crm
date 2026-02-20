<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class MenuUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:200"],
            "type" => ["required", "in:food,drink,bread,salad,sauce"],
            "price" => ["nullable", "numeric", "min:0"],
            "stock_quantity" => ["nullable", "integer", "min:0"],
            "unit" => ["nullable", "string", "max:20"],
            "description" => ["nullable", "string"],
        ];
    }
}
