<?php
//
//namespace App\Http\Requests\Administration\Tenant;
//
//use App\Models\Administration\TenantPosition;
//use Illuminate\Foundation\Http\FormRequest;
//use Illuminate\Validation\Rule;
//
//class TenantRequest extends FormRequest
//{
//    public function authorize(): bool
//    {
//        return true;
//    }
//
//    public function rules(): array
//    {
//        $id = $this->route('id');
//
//        return [
//            'name' => [
//                'required',
//                'string',
//                'max:255',
//                Rule::unique('tenants', 'name')->ignore($id, 'id'),
//            ],
//            'domain' => [
//                'required',
//                'string',
//                'max:255',
//                Rule::unique('tenants', 'domain')->ignore($id, 'id'),
//            ],
//
//            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
//            'campus_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
//            'country_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
//
//            'address' => ['nullable', 'string', 'max:255'],
//            'phone' => ['nullable', 'string', 'max:50'],
//            'email' => ['nullable', 'email', 'max:255'],
//            'legal_id' => ['nullable', 'string', 'max:255'],
//            'legal_id_type' => ['nullable', 'string', 'max:255'],
//            'is_active' => ['nullable', 'boolean'],
//            'business_name' => ['nullable', 'string', 'max:255'],
//            'campus_type' => ['nullable', 'string', 'max:255'],
//            'slogan' => ['nullable', 'string', 'max:255'],
//            'amie_code' => ['nullable', 'string', 'max:255'],
//            'city' => ['nullable', 'string', 'max:255'],
//            'state' => ['nullable', 'string', 'max:255'],
//            'country' => ['nullable', 'string', 'max:255'],
//            'country_logo_position_right' => ['nullable', 'boolean'],
//            'zip' => ['nullable', 'string', 'max:50'],
//
//            'authorities' => ['nullable', 'array'],
//            'authorities.*.person_id' => ['required_with:authorities', 'uuid', 'exists:persons,id'],
//            'authorities.*.position_id' => ['required_with:authorities', 'uuid', 'exists:positions,id'],
//            'authorities.*.signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
//            'authorities.*.is_active' => ['nullable', 'boolean'],
//            'authorities.*.start_date' => ['nullable', 'date'],
//            'authorities.*.end_date' => ['nullable', 'date'],
//        ];
//    }
//
//    public function withValidator($validator): void
//    {
//        $validator->after(function ($validator) {
//            $authorities = $this->input('authorities', []);
//            $currentTenantId = $this->route('id');
//
//            if (! is_array($authorities)) {
//                return;
//            }
//
//            $seen = [];
//
//            foreach ($authorities as $index => $authority) {
//                $personId = $authority['person_id'] ?? null;
//                $positionId = $authority['position_id'] ?? null;
//                $startDate = $authority['start_date'] ?? null;
//                $endDate = $authority['end_date'] ?? null;
//
//                if ($startDate && $endDate && $endDate < $startDate) {
//                    $validator->errors()->add(
//                        "authorities.$index.end_date",
//                        __('administration/tenant.validation.custom.authorities_end_date_after_or_equal')
//                    );
//                }
//
//                if ($personId && $positionId) {
//                    $compositeKey = $personId . '|' . $positionId;
//
//                    if (isset($seen[$compositeKey])) {
//                        $validator->errors()->add(
//                            "authorities.$index.person_id",
//                            __('administration/tenant.validation.custom.authorities_duplicate_in_request')
//                        );
//                    }
//
//                    $seen[$compositeKey] = true;
//
//                    if ($currentTenantId) {
//                        $exists = TenantPosition::query()
//                            ->where('tenant_id', $currentTenantId)
//                            ->where('person_id', $personId)
//                            ->where('position_id', $positionId)
//                            ->exists();
//
//                        if ($exists) {
//                            $validator->errors()->add(
//                                "authorities.$index.person_id",
//                                __('administration/tenant.validation.custom.authorities_duplicate_in_tenant')
//                            );
//                        }
//                    }
//                }
//            }
//        });
//    }
//
//    public function attributes(): array
//    {
//        return __('administration/tenant.validation.attributes');
//    }
//
//    public function messages(): array
//    {
//        return __('administration/tenant.validation.custom');
//    }
//}


//----------------------- nueva version ------------------------


namespace App\Http\Requests\Administration\Tenant;

use App\Models\Administration\TenantPosition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenants', 'name')->ignore($id, 'id'),
            ],
            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($id, 'id'),
            ],

            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
            'campus_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
            'country_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],

            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'legal_id' => ['nullable', 'string', 'max:255'],
            'legal_id_type' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'campus_type' => ['nullable', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'amie_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'country_logo_position_right' => ['nullable', 'boolean'],
            'zip' => ['nullable', 'string', 'max:50'],

            'authorities' => ['nullable', 'array'],
            'authorities.*.id' => ['nullable', 'uuid', 'exists:tenant_positions,id'],
            'authorities.*.person_id' => ['required_with:authorities', 'uuid', 'exists:persons,id'],
            'authorities.*.position_id' => ['required_with:authorities', 'uuid', 'exists:positions,id'],
            'authorities.*.signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
            'authorities.*.order_to_sign' => ['required_with:authorities', 'integer', 'min:1'],
            'authorities.*.is_active' => ['nullable', 'boolean'],
            'authorities.*.start_date' => ['nullable', 'date'],
            'authorities.*.end_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $authorities = $this->input('authorities', []);
            $currentTenantId = $this->route('id');

            if (!is_array($authorities)) {
                return;
            }

            $seenCombinations = [];
            $seenOrder = [];

            foreach ($authorities as $index => $authority) {
                $authorityId = $authority['id'] ?? null;
                $personId = $authority['person_id'] ?? null;
                $positionId = $authority['position_id'] ?? null;
                $orderToSign = $authority['order_to_sign'] ?? null;
                $startDate = $authority['start_date'] ?? null;
                $endDate = $authority['end_date'] ?? null;

                if ($startDate && $endDate && $endDate < $startDate) {
                    $validator->errors()->add(
                        "authorities.$index.end_date",
                        __('administration/tenant.validation.custom.authorities_end_date_after_or_equal')
                    );
                }

                if ($personId && $positionId) {
                    $compositeKey = $personId . '|' . $positionId;

                    if (isset($seenCombinations[$compositeKey])) {
                        $validator->errors()->add(
                            "authorities.$index.person_id",
                            __('administration/tenant.validation.custom.authorities_duplicate_in_request')
                        );
                    } else {
                        $seenCombinations[$compositeKey] = true;
                    }

                    if ($currentTenantId) {
                        $exists = TenantPosition::query()
                            ->where('tenant_id', $currentTenantId)
                            ->where('person_id', $personId)
                            ->where('position_id', $positionId)
                            ->when($authorityId, fn($q) => $q->where('id', '!=', $authorityId))
                            ->exists();

                        if ($exists) {
                            $validator->errors()->add(
                                "authorities.$index.person_id",
                                __('administration/tenant.validation.custom.authorities_duplicate_in_tenant')
                            );
                        }
                    }
                }

                if ($orderToSign !== null) {
                    if (isset($seenOrder[$orderToSign])) {
                        $validator->errors()->add(
                            "authorities.$index.order_to_sign",
                            __('administration/tenant_position.validation_sync.custom.order_to_sign_unique')
                        );
                    } else {
                        $seenOrder[$orderToSign] = true;
                    }
                }

                if ($authorityId && $currentTenantId) {
                    $belongsToTenant = TenantPosition::query()
                        ->where('id', $authorityId)
                        ->where('tenant_id', $currentTenantId)
                        ->exists();

                    if (!$belongsToTenant) {
                        $validator->errors()->add(
                            "authorities.$index.id",
                            __('administration/tenant_position.validation_sync.custom.invalid_position_for_tenant')
                        );
                    }
                }
            }
        });
    }

    public function attributes(): array
    {
        return __('administration/tenant.validation.attributes');
    }

    public function messages(): array
    {
        return __('administration/tenant.validation.custom');
    }
}
