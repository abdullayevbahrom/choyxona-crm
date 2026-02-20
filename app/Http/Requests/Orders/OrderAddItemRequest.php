<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class OrderAddItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "menu_item_id" => ["required", "integer", "exists:menu_items,id"],
            "quantity" => ["nullable", "integer", "min:1", "max:1000"],
            "notes" => ["nullable", "string", "max:500"],
        ];
    }
}
