<?php

namespace App\Http\Requests\Calendar\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start' => ['sometimes', 'nullable', 'date'],
            'end' => ['sometimes', 'nullable', 'date', 'after_or_equal:start'],
            'event_type_id' => ['sometimes', 'nullable', 'uuid'],
            'created_by_me' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'visibility' => ['sometimes', 'nullable', 'string', Rule::in(['private', 'restricted', 'public_tenant'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return __('validation/calendar/event.list.custom');
    }

    public function attributes(): array
    {
        return __('validation/calendar/event.list.attributes');
    }
}
