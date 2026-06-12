<?php

namespace App\Http\Requests\Support;

use App\Enums\Support\SupportTicketCategory;
use App\Enums\Support\SupportTicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],

            'category' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(SupportTicketCategory::class),
            ],

            'priority' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(SupportTicketPriority::class),
            ],
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
