<?php

namespace App\Http\Requests\Academic\GradeComponentTemplate;

use App\Models\Academic\GradeComponentDefinition;
use Illuminate\Foundation\Http\FormRequest;

class StoreGradeComponentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'evaluation_period_id' => ['required', 'uuid', 'exists:evaluation_periods,id'],

            'educational_level_id' => ['nullable', 'uuid', 'exists:educational_levels,id'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'specialty_id' => ['nullable', 'uuid', 'exists:specialties,id'],
            'modality_id' => ['nullable', 'uuid', 'exists:modalities,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:shifts,id'],

            'grading_mode' => ['required', 'string', 'in:basic_100,mixed_70_30,qualitative'],

            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.grade_component_definition_id' => [
                'required',
                'uuid',
                'exists:grade_component_definitions,id',
            ],

            'items.*.weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.max_score' => ['nullable', 'numeric', 'min:0'],
            'items.*.default_order' => ['nullable', 'integer', 'min:1'],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.is_system_calculated' => ['nullable', 'boolean'],
            'items.*.is_active' => ['nullable', 'boolean'],
            'items.*.settings' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = collect($this->input('items', []));

            $definitionIds = $items
                ->pluck('grade_component_definition_id')
                ->filter()
                ->unique()
                ->values();

            $definitions = GradeComponentDefinition::query()
                ->whereIn('id', $definitionIds)
                ->get()
                ->keyBy(fn ($definition) => (string) $definition->id);

            $numericWeight = $items
                ->filter(function ($item) use ($definitions) {
                    $definition = $definitions->get((string) ($item['grade_component_definition_id'] ?? ''));

                    return $definition?->component_type === 'numeric';
                })
                ->sum(fn ($item) => (float) ($item['weight'] ?? 0));

            if ($numericWeight > 100) {
                $validator->errors()->add(
                    'items',
                    __('messages.grade_component_template.weight_exceeds_100')
                );
            }
        });
    }

    public function attributes(): array
    {
        $attributes = trans('validation/Academic/grade_component_template.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
