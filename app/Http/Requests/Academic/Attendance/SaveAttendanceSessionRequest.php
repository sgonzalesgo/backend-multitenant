<?php

namespace App\Http\Requests\Academic\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAttendanceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = ['present', 'absent', 'late', 'excused'];

        return [
            'observation' => ['nullable', 'string', 'max:5000'],
            'close' => ['sometimes', 'boolean'],

            'records' => ['required', 'array', 'min:1'],
            'evaluation_period_id' => ['required', 'uuid', 'exists:evaluation_periods,id'],

            'records.*.id' => ['nullable', 'uuid', 'exists:attendance_records,id'],
            'records.*.enrollment_id' => ['required', 'uuid', 'exists:enrollments,id'],
            'records.*.student_id' => ['required', 'uuid', 'exists:students,id'],
            'records.*.person_id' => ['required', 'uuid', 'exists:persons,id'],

            'records.*.status' => ['required', 'string', Rule::in($statuses)],
            'records.*.late_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'records.*.observation' => ['nullable', 'string', 'max:5000'],
            'instructor_id' => ['required', 'uuid', 'exists:instructors,id'],
        ];
    }

    public function messages(): array
    {
        return __('validation/Academic/attendance.custom');
    }

    public function attributes(): array
    {
        return __('validation/Academic/attendance.attributes');
    }
}
