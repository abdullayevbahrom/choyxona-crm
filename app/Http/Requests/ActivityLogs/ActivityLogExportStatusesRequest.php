<?php

namespace App\Http\Requests\ActivityLogs;

use Illuminate\Foundation\Http\FormRequest;

class ActivityLogExportStatusesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:20'],
            'ids.*' => ['integer', 'exists:activity_log_exports,id'],
        ];
    }
}
