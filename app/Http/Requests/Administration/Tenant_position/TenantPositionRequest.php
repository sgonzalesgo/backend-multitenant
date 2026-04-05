<?php

namespace App\Http\Requests\Administration\Tenant_position;

use Illuminate\Foundation\Http\FormRequest;

class TenantPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'tenant_id' => [
                'required',
                'uuid',
                'exists:tenants,id',
            ],
            'person_id' => [
                'required',
                'uuid',
                'exists:persons,id',
            ],
            'position_id' => [
                'required',
                'uuid',
                'exists:positions,id',
            ],
            'signature' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,svg,webp',
                'max:2048',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }

    public function attributes(): array
    {
        return __('administration/tenant_position.validation.attributes');
    }

    public function messages(): array
    {
        return __('administration/tenant_position.validation.custom');
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tenantId = $this->input('tenant_id');
            $personId = $this->input('person_id');
            $positionId = $this->input('position_id');
            $currentId = $this->route('id');

            if (! $tenantId || ! $personId || ! $positionId) {
                return;
            }

            $exists = \App\Models\Administration\TenantPosition::query()
                ->where('tenant_id', $tenantId)
                ->where('person_id', $personId)
                ->where('position_id', $positionId)
                ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'person_id',
                    __('administration/tenant_position.validation.custom.person_position_unique')
                );
            }
        });
    }
}
