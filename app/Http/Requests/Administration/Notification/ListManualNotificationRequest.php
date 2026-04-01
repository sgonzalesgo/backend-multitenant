<?php

namespace App\Http\Requests\Administration\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListManualNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'archived' => ['sometimes', 'string', Rule::in(['without', 'only', 'with'])],
        ];
    }

    public function messages(): array
    {
        return __('validation/notification/manual_list.custom');
    }

    public function attributes(): array
    {
        return __('validation/notification/manual_list.attributes');
    }
}
