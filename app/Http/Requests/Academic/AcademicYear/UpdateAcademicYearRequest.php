<?php

namespace App\Http\Requests\Academic\AcademicYear;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function resolveCurrentTenantId(): ?string
    {
        if ($current = Tenant::current()) {
            return (string) $current->id;
        }

        $user = auth()->user();

        if (! $user || ! method_exists($user, 'token')) {
            return null;
        }

        $token = $user->token();

        if (! $token || empty($token->tenant_id)) {
            return null;
        }

        return (string) $token->tenant_id;
    }

    public function rules(): array
    {
        $academicYear = $this->route('academicYear');

        if (is_object($academicYear)) {
            $academicYear = $academicYear->id;
        }

        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.academic_years.tenant_not_resolved'));
        }

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('academic_years', 'code')
                    ->ignore($academicYear)
                    ->where(function ($query) use ($tenantId) {
                        return $query
                            ->where('tenant_id', $tenantId)
                            ->whereNull('deleted_at');
                    }),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('academic_years', 'name')
                    ->ignore($academicYear)
                    ->where(function ($query) use ($tenantId) {
                        return $query
                            ->where('tenant_id', $tenantId)
                            ->whereNull('deleted_at');
                    }),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'is_current' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $academicYear = $this->route('academicYear');

            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            if (is_object($academicYear)) {
                $startDate = $startDate ?? optional($academicYear->start_date)->format('Y-m-d');
                $endDate = $endDate ?? optional($academicYear->end_date)->format('Y-m-d');
            }

            if ($startDate && $endDate && $endDate <= $startDate) {
                $validator->errors()->add(
                    'end_date',
                    __('validation.after', [
                        'attribute' => __('validation/academic/academic-year.attributes.end_date'),
                        'date' => __('validation/academic/academic-year.attributes.start_date'),
                    ])
                );
            }
        });
    }

    public function messages(): array
    {
        return __('validation/academic/academic-year.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/academic-year.attributes');
    }
}
