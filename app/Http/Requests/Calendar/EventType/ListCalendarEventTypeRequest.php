<?php

namespace App\Http\Requests\Calendar\EventType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCalendarEventTypeRequest extends FormRequest
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
            'is_active' => ['sometimes', 'boolean'],
            'is_system' => ['sometimes', 'boolean'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'paginate' => ['sometimes', 'boolean'],
            'sort_by' => ['sometimes', 'string', Rule::in(['name', 'code', 'created_at'])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return __('validation/calendar/event_type.list.custom');
    }

    public function attributes(): array
    {
        return __('validation/calendar/event_type.list.attributes');
    }
}
