<?php

namespace App\Http\Requests\Academic\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceDaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'course_id' => ['required', 'uuid', 'exists:courses,id'],
            'parallel_id' => ['required', 'uuid', 'exists:parallels,id'],
            'subject_id' => ['required', 'uuid', 'exists:subjects,id'],
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
