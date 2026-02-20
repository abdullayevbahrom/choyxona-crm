<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Foundation\Http\FormRequest;

class RoomStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "number" => ["required", "string", "max:20", "unique:rooms,number"],
            "name" => ["nullable", "string", "max:100"],
            "capacity" => ["nullable", "integer", "min:1"],
            "description" => ["nullable", "string"],
        ];
    }
}
