<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomId = $this->route("room")?->id;

        return [
            "number" => [
                "required",
                "string",
                "max:20",
                Rule::unique("rooms", "number")->ignore($roomId),
            ],
            "name" => ["nullable", "string", "max:100"],
            "capacity" => ["nullable", "integer", "min:1"],
            "description" => ["nullable", "string"],
        ];
    }
}
