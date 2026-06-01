<?php

namespace App\Http\Requests\Academic\GradeComponentDefinition;

use Illuminate\Foundation\Http\FormRequest;

class GradeComponentDefinitionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'component_type' => ['nullable', 'string', 'in:numeric,behavior,qualitative'],
            'component_key' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', 'in:component_key,component_type,code,name,is_active,created_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/Academic/grade_component_definition.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
