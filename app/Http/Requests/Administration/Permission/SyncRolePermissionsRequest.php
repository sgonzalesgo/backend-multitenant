<?php

namespace App\Http\Requests\Administration\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // o Gate: can('Update permissions')
    }

    public function rules(): array
    {
        return [
            'permissions'   => ['required', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'id')],
        ];
    }

    public function messages(): array
    {
        return __('validation/administration/role_permission.custom');
    }

    public function attributes(): array
    {
        return __('validation/administration/role_permission.attributes');
    }
}
