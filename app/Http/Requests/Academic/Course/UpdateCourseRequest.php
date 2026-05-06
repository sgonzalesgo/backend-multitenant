<?php

namespace App\Http\Requests\Academic\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'educational_level_id' => ['sometimes', 'required', 'uuid', 'exists:educational_levels,id'],
            'instructor_id' => ['sometimes', 'required', 'uuid', 'exists:instructors,id'],

            'code' => ['sometimes', 'required', 'string', 'max:50'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            'capacity' => ['sometimes', 'required', 'integer', 'min:0'],
            'level_number' => ['sometimes', 'required', 'integer', 'min:1', 'max:999'],
            'credits' => ['nullable', 'integer', 'min:0', 'max:999'],
            'theoretical_hours' => ['nullable', 'integer', 'min:0', 'max:999'],
            'practical_hours' => ['nullable', 'integer', 'min:0', 'max:999'],
            'total_hours' => ['nullable', 'integer', 'min:0', 'max:999'],

            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/Academic/course.custom');

        return is_array($messages) ? $messages : [];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/Academic/course.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
