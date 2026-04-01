<?php

namespace App\Http\Requests\Calendar\EventType;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'nullable', 'array'],

            'settings.category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'settings.requires_location' => ['sometimes', 'boolean'],
            'settings.requires_audience' => ['sometimes', 'boolean'],
            'settings.supports_attendance' => ['sometimes', 'boolean'],
            'settings.supports_response' => ['sometimes', 'boolean'],
            'settings.supports_recurrence' => ['sometimes', 'boolean'],
            'settings.default_all_day' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return __('validation/calendar/event_type.store.custom');
    }

    public function attributes(): array
    {
        return __('validation/calendar/event_type.store.attributes');
    }
}
