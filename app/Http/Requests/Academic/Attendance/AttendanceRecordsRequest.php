<?php


namespace App\Http\Requests\Academic\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRecordsRequest extends FormRequest
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
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'string', 'in:present,absent,late,excused'],
        ];
    }
}
