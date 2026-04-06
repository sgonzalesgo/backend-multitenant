<?php

namespace App\Http\Requests\Administration\Tenant_position;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TenantPositionSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [
                'required',
                'uuid',
                'exists:tenants,id',
            ],

            'positions' => [
                'required',
                'array',
            ],

            'positions.*.id' => [
                'nullable',
                'uuid',
                'exists:tenant_positions,id',
            ],

            'positions.*.person_id' => [
                'required',
                'uuid',
                'exists:persons,id',
            ],

            'positions.*.position_id' => [
                'required',
                'uuid',
                'exists:positions,id',
            ],

            'positions.*.signature' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,svg,webp',
                'max:2048',
            ],

            'positions.*.order_to_sign' => [
                'required',
                'integer',
                'min:1',
            ],

            'positions.*.is_active' => [
                'nullable',
                'boolean',
            ],

            'positions.*.start_date' => [
                'nullable',
                'date',
            ],

            'positions.*.end_date' => [
                'nullable',
                'date',
                'after_or_equal:positions.*.start_date',
            ],
        ];
    }

    public function attributes(): array
    {
        return __('administration/tenant_position.validation_sync.attributes');
    }

    public function messages(): array
    {
        return __('administration/tenant_position.validation_sync.custom');
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            $tenantId = $this->input('tenant_id');
            $positions = $this->input('positions', []);

            if (! $tenantId || ! is_array($positions) || empty($positions)) {
                return;
            }

            $combinations = [];
            $orderValues = [];

            foreach ($positions as $index => $item) {
                $personId = $item['person_id'] ?? null;
                $positionId = $item['position_id'] ?? null;
                $currentId = $item['id'] ?? null;
                $orderToSign = $item['order_to_sign'] ?? null;

                if ($personId && $positionId) {
                    $combinationKey = $tenantId . '|' . $personId . '|' . $positionId;

                    if (isset($combinations[$combinationKey])) {
                        $validator->errors()->add(
                            "positions.$index.person_id",
                            __('administration/tenant_position.validation_sync.custom.person_position_unique')
                        );
                    } else {
                        $combinations[$combinationKey] = true;
                    }

                    $exists = \App\Models\Administration\TenantPosition::query()
                        ->where('tenant_id', $tenantId)
                        ->where('person_id', $personId)
                        ->where('position_id', $positionId)
                        ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add(
                            "positions.$index.person_id",
                            __('administration/tenant_position.validation_sync.custom.person_position_unique')
                        );
                    }
                }

                if ($orderToSign !== null) {
                    if (isset($orderValues[$orderToSign])) {
                        $validator->errors()->add(
                            "positions.$index.order_to_sign",
                            __('administration/tenant_position.validation_sync.custom.order_to_sign_unique')
                        );
                    } else {
                        $orderValues[$orderToSign] = true;
                    }
                }

                if (! empty($currentId)) {
                    $belongsToTenant = \App\Models\Administration\TenantPosition::query()
                        ->where('id', $currentId)
                        ->where('tenant_id', $tenantId)
                        ->exists();

                    if (! $belongsToTenant) {
                        $validator->errors()->add(
                            "positions.$index.id",
                            __('administration/tenant_position.validation_sync.custom.invalid_position_for_tenant')
                        );
                    }
                }

                $startDate = $item['start_date'] ?? null;
                $endDate = $item['end_date'] ?? null;

                if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
                    $validator->errors()->add(
                        "positions.$index.end_date",
                        __('administration/tenant_position.validation_sync.custom.end_date_after_or_equal')
                    );
                }
            }
        });
    }
}
