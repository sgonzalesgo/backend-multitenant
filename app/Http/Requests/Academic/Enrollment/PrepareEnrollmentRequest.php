<?php

namespace App\Http\Requests\Academic\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class PrepareEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_id' => ['required', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'legal_id' => __('validation/Academic/enrollment.attributes.legal_id'),
        ];
    }
}
