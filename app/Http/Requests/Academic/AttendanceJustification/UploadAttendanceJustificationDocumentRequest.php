<?php

namespace App\Http\Requests\Academic\AttendanceJustification;

use Illuminate\Foundation\Http\FormRequest;

class UploadAttendanceJustificationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }

    public function attributes(): array
    {
        return trans('validation/academic/attendance_justification.attributes');
    }
}
