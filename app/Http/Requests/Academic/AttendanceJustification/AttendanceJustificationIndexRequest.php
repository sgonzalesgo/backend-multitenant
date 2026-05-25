<?php

namespace App\Http\Requests\Academic\AttendanceJustification;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceJustificationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'justification_type' => ['nullable', 'string', 'in:medical,family,institutional,other'],

            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
            'person_id' => ['nullable', 'uuid', 'exists:persons,id'],
            'attendance_session_id' => ['nullable', 'uuid', 'exists:attendance_sessions,id'],
            'attendance_record_id' => ['nullable', 'uuid', 'exists:attendance_records,id'],

            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return trans('validation/academic/attendance_justification.attributes');
    }
}
