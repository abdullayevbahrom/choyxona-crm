<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class OrderHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "room_id" => ["nullable", "integer", "exists:rooms,id"],
            "date_from" => ["nullable", "date"],
            "date_to" => ["nullable", "date"],
            "status" => ["nullable", "in:closed,cancelled"],
        ];
    }
}
