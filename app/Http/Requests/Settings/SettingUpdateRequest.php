<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'notification_from_name' => ['nullable', 'string', 'max:150'],
            'notification_from_email' => [
                'nullable',
                'email:rfc,dns',
                'max:190',
            ],
            'notification_logo_url' => ['nullable', 'url', 'max:255'],
            'notification_logo_file' => [
                'nullable',
                'file',
                'image',
                'mimes:png,jpg,jpeg,webp,gif,bmp',
                'max:2048',
            ],
            'notification_logo_size' => [
                'nullable',
                'integer',
                'min:16',
                'max:48',
            ],
        ];
    }
}
