<?php

namespace App\Http\Requests\Support;

use App\Enums\Support\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeSupportTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::enum(SupportTicketStatus::class),
            ],

            'comment' => ['nullable', 'string'],
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
