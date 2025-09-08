<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'q'              => ['nullable','string','max:200'],
            'event'          => ['nullable','string','max:100'],
            'user_id'        => ['nullable','string','max:64'],
            'auditable_type' => ['nullable','string','max:191'],
            'auditable_id'   => ['nullable','string','max:64'],
            'tenant_id'      => ['nullable','string','max:64'],
            'date_from'      => ['nullable','date'],
            'date_to'        => ['nullable','date','after_or_equal:date_from'],
            'per_page'       => ['nullable','integer','min:1','max:200'],
            'sort'           => ['nullable','string','in:created_at,event,actor_id,tenant_id'],
            'dir'            => ['nullable','string','in:asc,desc'],
        ];
    }
}
