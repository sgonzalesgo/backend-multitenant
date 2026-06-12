<?php

namespace App\Http\Requests\Support;

use App\Enums\Support\SupportTicketCategory;
use App\Enums\Support\SupportTicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],

            'category' => [
                'nullable',
                'string',
                Rule::enum(SupportTicketCategory::class),
            ],

            'priority' => [
                'nullable',
                'string',
                Rule::enum(SupportTicketPriority::class),
            ],

            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120'],
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
