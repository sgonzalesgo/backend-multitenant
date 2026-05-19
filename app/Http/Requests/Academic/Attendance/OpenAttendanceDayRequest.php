<?php

namespace App\Http\Requests\Academic\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class OpenAttendanceDayRequest extends FormRequest
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
            'shift_id' => ['required', 'uuid', 'exists:shifts,id'],
            'subject_id' => ['required', 'uuid', 'exists:subjects,id'],
            'specialty_id' => ['nullable', 'uuid', 'exists:specialties,id'],
            'modality_id' => ['required', 'uuid', 'exists:modalities,id'],
            'instructor_id' => ['required', 'uuid', 'exists:instructors,id'],
            'calendar_event_id' => ['required', 'uuid', 'exists:calendar_events,id'],
            'attendance_date' => ['required', 'date'],
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
