<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class AssignSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/support/ticket.custom');

        return is_array($messages) ? $messages : [];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/support/ticket.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
