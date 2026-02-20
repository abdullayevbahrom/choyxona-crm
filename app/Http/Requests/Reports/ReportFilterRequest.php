<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
