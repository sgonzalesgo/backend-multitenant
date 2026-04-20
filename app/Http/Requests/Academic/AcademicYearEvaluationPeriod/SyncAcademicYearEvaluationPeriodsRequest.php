<?php

namespace App\Http\Requests\Academic\AcademicYearEvaluationPeriod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncAcademicYearEvaluationPeriodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluation_periods' => ['required', 'array', 'min:1'],
            'evaluation_periods.*.evaluation_period_id' => [
                'required',
                'uuid',
                Rule::exists('evaluation_periods', 'id')->whereNull('deleted_at'),
            ],
            'evaluation_periods.*.order' => ['required', 'integer', 'min:1'],
            'evaluation_periods.*.start_date' => ['required', 'date'],
            'evaluation_periods.*.end_date' => ['required', 'date'],
            'evaluation_periods.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('evaluation_periods', []);

            if (! is_array($items) || empty($items)) {
                return;
            }

            $periodIds = [];
            $orders = [];

            foreach ($items as $index => $item) {
                $periodId = $item['evaluation_period_id'] ?? null;
                $order = $item['order'] ?? null;
                $startDate = $item['start_date'] ?? null;
                $endDate = $item['end_date'] ?? null;

                if ($periodId) {
                    if (in_array($periodId, $periodIds, true)) {
                        $validator->errors()->add(
                            "evaluation_periods.$index.evaluation_period_id",
                            __('validation/academic/academic-year-evaluation-period.messages.duplicate_evaluation_period')
                        );
                    }

                    $periodIds[] = $periodId;
                }

                if ($order !== null) {
                    if (in_array((int) $order, $orders, true)) {
                        $validator->errors()->add(
                            "evaluation_periods.$index.order",
                            __('validation/academic/academic-year-evaluation-period.messages.duplicate_order')
                        );
                    }

                    $orders[] = (int) $order;
                }

                if ($startDate && $endDate && $endDate <= $startDate) {
                    $validator->errors()->add(
                        "evaluation_periods.$index.end_date",
                        __('validation.after', [
                            'attribute' => __('validation/academic/academic-year-evaluation-period.attributes.end_date'),
                            'date' => __('validation/academic/academic-year-evaluation-period.attributes.start_date'),
                        ])
                    );
                }
            }

            usort($items, function ($a, $b) {
                return strcmp($a['start_date'] ?? '', $b['start_date'] ?? '');
            });

            for ($i = 0; $i < count($items) - 1; $i++) {
                $currentEnd = $items[$i]['end_date'] ?? null;
                $nextStart = $items[$i + 1]['start_date'] ?? null;

                if ($currentEnd && $nextStart && $nextStart <= $currentEnd) {
                    $validator->errors()->add(
                        "evaluation_periods.$i.end_date",
                        __('validation/academic/academic-year-evaluation-period.messages.date_overlap')
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return __('validation/academic/academic-year-evaluation-period.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/academic-year-evaluation-period.attributes');
    }
}
