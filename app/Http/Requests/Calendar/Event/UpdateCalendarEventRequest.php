<?php

namespace App\Http\Requests\Calendar\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type_id' => ['sometimes', 'nullable', 'uuid'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'url' => ['sometimes', 'nullable', 'url', 'max:500'],

            'start_at' => ['sometimes', 'required', 'date'],
            'end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_at'],
            'all_day' => ['sometimes', 'required', 'boolean'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:100'],

            'status' => ['sometimes', 'nullable', 'string', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'visibility' => ['sometimes', 'nullable', 'string', Rule::in(['private', 'restricted', 'public_tenant'])],
            'editable_by' => ['sometimes', 'nullable', 'string', Rule::in(['creator_only', 'admins', 'system'])],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],

            'is_recurring' => ['sometimes', 'boolean'],
            'recurrence_rule' => ['sometimes', 'nullable', 'string'],

            'google_sync_enabled' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'nullable', 'array'],

            'participants' => ['sometimes', 'nullable', 'array'],
            'participants.*.user_id' => ['sometimes', 'nullable', 'uuid'],
            'participants.*.person_id' => ['sometimes', 'nullable', 'uuid'],
            'participants.*.participant_type' => ['required_with:participants', 'string', Rule::in(['user', 'student', 'teacher', 'parent', 'external'])],
            'participants.*.role' => ['sometimes', 'nullable', 'string', Rule::in(['owner', 'organizer', 'attendee', 'viewer'])],
            'participants.*.response_status' => ['sometimes', 'nullable', 'string', Rule::in(['pending', 'accepted', 'declined', 'tentative'])],
            'participants.*.is_required' => ['sometimes', 'boolean'],
            'participants.*.can_view' => ['sometimes', 'boolean'],
            'participants.*.can_receive_notifications' => ['sometimes', 'boolean'],

            'audiences' => ['sometimes', 'nullable', 'array'],
            'audiences.*.audience_type' => ['required_with:audiences', 'string', Rule::in(['tenant', 'role', 'course', 'section', 'grade', 'department', 'user', 'student', 'teacher', 'parent'])],
            'audiences.*.audience_id' => ['sometimes', 'nullable', 'uuid'],
            'audiences.*.filters' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return __('validation/calendar/event.update.custom');
    }

    public function attributes(): array
    {
        return __('validation/calendar/event.update.attributes');
    }
}
