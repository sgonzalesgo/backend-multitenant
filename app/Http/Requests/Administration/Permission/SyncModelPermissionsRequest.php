<?php

namespace App\Http\Requests\Administration\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncModelPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'       => ['required', 'string', Rule::exists('users', 'id')],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'id')],
        ];
    }

    public function messages(): array
    {
        return __('validation/administration/model_permission.custom');
    }

    public function attributes(): array
    {
        return __('validation/administration/model_permission.attributes');
    }
}
