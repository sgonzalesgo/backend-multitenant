<?php

namespace App\Http\Requests\Administration\Notification;

use Illuminate\Foundation\Http\FormRequest;

class SendManualNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'uuid', 'distinct', 'exists:users,id'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
            'route' => ['nullable', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return __('validation/notification/manual_send.custom');
    }

    public function attributes(): array
    {
        return __('validation/notification/manual_send.attributes');
    }
}
