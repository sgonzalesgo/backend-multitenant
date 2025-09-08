<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; } // protege con permisos en la ruta

    public function rules(): array
    {
        return [
            'event'          => ['required','string','max:100'],
            'auditable_type' => ['nullable','string','max:191'],
            'auditable_id'   => ['nullable','string','max:64'],
            'description'    => ['nullable','string','max:500'],
            'old_values'     => ['nullable','array'],
            'new_values'     => ['nullable','array'],
            'tenant_id'      => ['nullable','string','max:64'],
            'meta'           => ['nullable','array'],
        ];
    }
}
