<?php

namespace App\Http\Requests\Academic\AcademicSchedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['sometimes', 'required', 'uuid', 'exists:academic_years,id'],
            'course_id' => ['sometimes', 'required', 'uuid', 'exists:courses,id'],
            'specialty_id' => ['nullable', 'uuid', 'exists:specialties,id'],
            'parallel_id' => ['sometimes', 'required', 'uuid', 'exists:parallels,id'],
            'modality_id' => ['sometimes', 'required', 'uuid', 'exists:modalities,id'],
            'shift_id' => ['sometimes', 'required', 'uuid', 'exists:shifts,id'],

            'status' => ['nullable', 'string', 'max:80'],
            'general_observation' => ['nullable', 'string', 'max:5000'],

            'check_conflicts' => ['sometimes', 'boolean'],

            'frequencies' => ['nullable', 'array', 'min:1'],

            'frequencies.*.id' => ['nullable', 'uuid', 'exists:academic_schedule_frequencies,id'],
            'frequencies.*.day_of_week' => ['required_with:frequencies', 'integer', 'between:1,7'],
            'frequencies.*.start_time' => ['required_with:frequencies', 'date_format:H:i'],
            'frequencies.*.end_time' => [
                'required_with:frequencies',
                'date_format:H:i',
                'after:frequencies.*.start_time',
            ],

            'frequencies.*.classroom_id' => ['required_with:frequencies', 'uuid', 'exists:classrooms,id'],
            'frequencies.*.subject_id' => ['required_with:frequencies', 'uuid', 'exists:subjects,id'],
            'frequencies.*.instructor_id' => ['required_with:frequencies', 'uuid', 'exists:instructors,id'],
            'frequencies.*.observation' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/Academic/academic_schedule.custom');

        return is_array($messages) ? $messages : [];
    }
}
