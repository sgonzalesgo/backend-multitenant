<?php

namespace App\Http\Requests\Academic\AttendanceJustification;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAttendanceJustificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_observation' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return trans('validation/academic/attendance_justification.attributes');
    }
}
