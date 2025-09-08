<?php

namespace App\Http\Requests\Administration\Permission;

// GLOBAL IMPORT
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Si querés, podés chequear Gate/Policy: return $this->user()->can('Create permissions');
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->where('guard_name', $this->input('guard_name', config('auth.defaults.guard', 'api'))),
            ],
            'guard_name' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return __('validation/administration/permission.custom');
    }

    public function attributes(): array
    {
        return __('validation/administration/permission.attributes');
    }
}
