<?php

namespace App\Http\Requests\Administration\tenant;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

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
                Rule::unique('tenants', 'name')->ignore($id),
            ],
            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($id),
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
        ];
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
