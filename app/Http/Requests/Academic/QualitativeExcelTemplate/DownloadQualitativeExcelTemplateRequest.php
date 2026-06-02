<?php

namespace App\Http\Requests\Academic\QualitativeExcelTemplate;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadQualitativeExcelTemplateRequest extends FormRequest
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

        return $token && ! empty($token->tenant_id)
            ? (string) $token->tenant_id
            : null;
    }

    public function rules(): array
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.qualitative_excel_template.tenant_not_resolved'));
        }

        return [
            'academic_year_id' => [
                'required',
                'uuid',
                Rule::exists('academic_years', 'id'),
            ],

            'evaluation_period_id' => [
                'required',
                'uuid',
                Rule::exists('evaluation_periods', 'id'),
            ],

            'course_id' => [
                'required',
                'uuid',
                Rule::exists('courses', 'id'),
            ],

            'parallel_id' => [
                'required',
                'uuid',
                Rule::exists('parallels', 'id'),
            ],

            'specialty_id' => [
                'nullable',
                'uuid',
                Rule::exists('specialties', 'id'),
            ],

            'modality_id' => [
                'nullable',
                'uuid',
                Rule::exists('modalities', 'id'),
            ],

            'shift_id' => [
                'nullable',
                'uuid',
                Rule::exists('shifts', 'id'),
            ],

            'subject_id' => [
                'required',
                'uuid',
                Rule::exists('subjects', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/qualitative-excel-template.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/qualitative-excel-template.attributes');
    }
}
