<?php

namespace App\Http\Requests\Academic\EnrollmentStatus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnrollmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $enrollmentStatus = $this->route('enrollmentStatus');

        if (is_object($enrollmentStatus)) {
            $enrollmentStatus = $enrollmentStatus->id;
        }

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('enrollment_statuses', 'code')
                    ->ignore($enrollmentStatus)
                    ->whereNull('deleted_at'),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('enrollment_statuses', 'name')
                    ->ignore($enrollmentStatus)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/enrollment-status.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/enrollment-status.attributes');
    }
}
