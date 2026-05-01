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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],

            'q' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],

            'category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'is_system' => ['nullable', 'boolean'],

            'paginate' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:name,code,created_at,updated_at,is_active,is_system'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
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
