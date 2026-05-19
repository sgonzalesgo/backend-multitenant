<?php

namespace App\Http\Requests\Academic\AcademicContext;

use App\Models\Academic\Course;
use Illuminate\Foundation\Http\FormRequest;

class AcademicContextFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],

            'academic_period_id' => ['nullable', 'uuid', 'exists:evaluation_periods,id'],

            'educational_level_id' => ['nullable', 'uuid', 'exists:educational_levels,id'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'specialty_id' => ['nullable', 'uuid', 'exists:specialties,id'],
            'parallel_id' => ['nullable', 'uuid', 'exists:parallels,id'],
            'modality_id' => ['nullable', 'uuid', 'exists:modalities,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:shifts,id'],

            'subject_id' => ['nullable', 'uuid', 'exists:subjects,id'],
            'instructor_id' => ['nullable', 'uuid', 'exists:instructors,id'],
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],

            'context' => ['nullable', 'string', 'in:attendance,grades,reports'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $context = $this->input('context');

            if ($context === 'grades') {
                $this->validateRequiredFields($validator, [
                    'academic_period_id',
                    'course_id',
                    'parallel_id',
                    'shift_id',
                    'subject_id',
                    'instructor_id',
                ]);

                $this->validateSpecialtyWhenRequired($validator);
            }

            if ($context === 'attendance') {
                $this->validateRequiredFields($validator, [
                    'course_id',
                    'parallel_id',
                    'shift_id',
                    'subject_id',
                    'instructor_id',
                ]);

                $this->validateSpecialtyWhenRequired($validator);
            }

            if ($context === 'reports') {
                $this->validateSpecialtyWhenRequired($validator);
            }
        });
    }

    protected function validateRequiredFields($validator, array $fields): void
    {
        foreach ($fields as $field) {
            if (! $this->filled($field)) {
                $validator->errors()->add(
                    $field,
                    __('validation.required', [
                        'attribute' => $field,
                    ])
                );
            }
        }
    }

    protected function validateSpecialtyWhenRequired($validator): void
    {
        $courseId = $this->input('course_id');

        if (! $courseId) {
            return;
        }

        $course = Course::query()
            ->with('educationalLevel:id,has_specialty')
            ->find($courseId);

        $requiresSpecialty = (bool) $course?->educationalLevel?->has_specialty;

        if ($requiresSpecialty && ! $this->filled('specialty_id')) {
            $validator->errors()->add(
                'specialty_id',
                __('validation.required', [
                    'attribute' => 'specialty_id',
                ])
            );
        }
    }

    public function messages(): array
    {
        $messages = trans('validation/Academic/academic_context.custom');

        return is_array($messages) ? $messages : [];
    }
}
