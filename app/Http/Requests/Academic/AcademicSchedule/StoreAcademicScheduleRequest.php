<?php

namespace App\Http\Requests\Academic\AcademicSchedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicScheduleRequest extends FormRequest
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
            'modality_id' => ['required', 'uuid', 'exists:modalities,id'],
            'shift_id' => ['required', 'uuid', 'exists:shifts,id'],

            'status' => ['nullable', 'string', 'max:80'],
            'general_observation' => ['nullable', 'string', 'max:5000'],

            'check_conflicts' => ['sometimes', 'boolean'],

            'frequencies' => ['required', 'array', 'min:1'],

            'frequencies.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'frequencies.*.start_time' => ['required', 'date_format:H:i'],
            'frequencies.*.end_time' => [
                'required',
                'date_format:H:i',
                'after:frequencies.*.start_time',
            ],

            'frequencies.*.classroom_id' => ['required', 'uuid', 'exists:classrooms,id'],
            'frequencies.*.subject_id' => ['required', 'uuid', 'exists:subjects,id'],
            'frequencies.*.instructor_id' => ['required', 'uuid', 'exists:instructors,id'],
            'frequencies.*.observation' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/Academic/academic_schedule.custom');

        return is_array($messages) ? $messages : [];
    }
}
