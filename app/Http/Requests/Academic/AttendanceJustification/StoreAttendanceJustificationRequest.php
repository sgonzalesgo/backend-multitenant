<?php

namespace App\Http\Requests\Academic\AttendanceJustification;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceJustificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_record_id' => ['required', 'uuid', 'exists:attendance_records,id'],

            'justification_type' => ['required', 'string', 'in:medical,family,institutional,other'],
            'reason' => ['required', 'string', 'max:5000'],
            'document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return trans('validation/academic/attendance_justification.attributes');
    }
}
