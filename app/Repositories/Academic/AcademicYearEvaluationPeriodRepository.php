<?php

namespace App\Repositories\Academic;

use App\Models\Academic\AcademicYearEvaluationPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearEvaluationPeriodRepository
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'order');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'order',
            'start_date',
            'end_date',
            'is_active',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'order';
        }

        $global = '';
        $academicYearId = '';
        $evaluationPeriodId = '';
        $order = '';
        $isActive = '';
        $startDate = '';
        $endDate = '';
        $createdAt = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $academicYearId = trim((string) Arr::get($decoded, 'columns.academic_year_id', ''));
                $evaluationPeriodId = trim((string) Arr::get($decoded, 'columns.evaluation_period_id', ''));
                $order = trim((string) Arr::get($decoded, 'columns.order', ''));
                $isActive = trim((string) Arr::get($decoded, 'columns.is_active', ''));
                $startDate = trim((string) Arr::get($decoded, 'columns.start_date', ''));
                $endDate = trim((string) Arr::get($decoded, 'columns.end_date', ''));
                $createdAt = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        return AcademicYearEvaluationPeriod::query()
            ->with([
                'academicYear:id,code,name,tenant_id,start_date,end_date,is_active',
                'evaluationPeriod:id,code,name,description,default_order,is_active',
            ])
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->whereHas('academicYear', function ($academicYearQuery) use ($global) {
                        $academicYearQuery
                            ->where('code', 'ilike', "%{$global}%")
                            ->orWhere('name', 'ilike', "%{$global}%");
                    })->orWhereHas('evaluationPeriod', function ($evaluationPeriodQuery) use ($global) {
                        $evaluationPeriodQuery
                            ->where('code', 'ilike', "%{$global}%")
                            ->orWhere('name', 'ilike', "%{$global}%")
                            ->orWhere('description', 'ilike', "%{$global}%");
                    });
                });
            })
            ->when($academicYearId !== '', fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($evaluationPeriodId !== '', fn ($query) => $query->where('evaluation_period_id', $evaluationPeriodId))
            ->when($order !== '', fn ($query) => $query->where('order', (int) $order))
            ->when($isActive !== '', function ($query) use ($isActive) {
                $normalized = strtolower($isActive);

                if (in_array($normalized, ['1', 'true', 'yes', 'activo', 'active'], true)) {
                    $query->where('is_active', true);
                }

                if (in_array($normalized, ['0', 'false', 'no', 'inactivo', 'inactive'], true)) {
                    $query->where('is_active', false);
                }
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('start_date', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('end_date', $endDate))
            ->when($createdAt !== '', fn ($query) => $query->whereDate('created_at', $createdAt))
            ->orderBy($sort, $dir)
            ->orderBy('start_date', 'asc')
            ->paginate($perPage);
    }

    public function create(array $data): AcademicYearEvaluationPeriod
    {
        return DB::transaction(function () use ($data) {
            $academicYearId = Arr::get($data, 'academic_year_id');
            $startDate = Arr::get($data, 'start_date');
            $endDate = Arr::get($data, 'end_date');

            $this->ensureDatesDoNotOverlap(
                academicYearId: $academicYearId,
                startDate: $startDate,
                endDate: $endDate
            );

            $academicYearEvaluationPeriod = AcademicYearEvaluationPeriod::query()->create([
                'academic_year_id' => $academicYearId,
                'evaluation_period_id' => Arr::get($data, 'evaluation_period_id'),
                'order' => Arr::get($data, 'order'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => Arr::get($data, 'is_active', true),
            ]);

            return $academicYearEvaluationPeriod
                ->load([
                    'academicYear:id,code,name,tenant_id,start_date,end_date,is_active',
                    'evaluationPeriod:id,code,name,description,default_order,is_active',
                ])
                ->refresh();
        });
    }

    public function update(AcademicYearEvaluationPeriod $academicYearEvaluationPeriod, array $data): AcademicYearEvaluationPeriod {
        return DB::transaction(function () use ($academicYearEvaluationPeriod, $data) {
            $academicYearId = $academicYearEvaluationPeriod->academic_year_id;
            $startDate = Arr::get($data, 'start_date', optional($academicYearEvaluationPeriod->start_date)->format('Y-m-d'));
            $endDate = Arr::get($data, 'end_date', optional($academicYearEvaluationPeriod->end_date)->format('Y-m-d'));

            $this->ensureDatesDoNotOverlap(
                academicYearId: $academicYearId,
                startDate: $startDate,
                endDate: $endDate,
                ignoreId: $academicYearEvaluationPeriod->id
            );

            $academicYearEvaluationPeriod->fill([
                'evaluation_period_id' => Arr::get($data, 'evaluation_period_id', $academicYearEvaluationPeriod->evaluation_period_id),
                'order' => Arr::get($data, 'order', $academicYearEvaluationPeriod->order),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => Arr::get($data, 'is_active', $academicYearEvaluationPeriod->is_active),
            ]);

            $academicYearEvaluationPeriod->save();

            return $academicYearEvaluationPeriod
                ->load([
                    'academicYear:id,code,name,tenant_id,start_date,end_date,is_active',
                    'evaluationPeriod:id,code,name,description,default_order,is_active',
                ])
                ->refresh();
        });
    }

    public function syncByAcademicYear(string $academicYearId, array $items): array
    {
        return DB::transaction(function () use ($academicYearId, $items) {
            AcademicYearEvaluationPeriod::query()
                ->where('academic_year_id', $academicYearId)
                ->delete();

            $created = [];

            foreach ($items as $item) {
                $created[] = AcademicYearEvaluationPeriod::query()->create([
                    'academic_year_id' => $academicYearId,
                    'evaluation_period_id' => $item['evaluation_period_id'],
                    'order' => $item['order'],
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'],
                    'is_active' => $item['is_active'] ?? true,
                ]);
            }

            return AcademicYearEvaluationPeriod::query()
                ->with([
                    'academicYear:id,code,name,tenant_id,start_date,end_date,is_active',
                    'evaluationPeriod:id,code,name,description,default_order,is_active',
                ])
                ->where('academic_year_id', $academicYearId)
                ->orderBy('order')
                ->orderBy('start_date')
                ->get()
                ->all();
        });
    }

    public function delete(AcademicYearEvaluationPeriod $academicYearEvaluationPeriod): void
    {
        $academicYearEvaluationPeriod->delete();
    }

    protected function ensureDatesDoNotOverlap(string $academicYearId, string $startDate, string $endDate, ?string $ignoreId = null): void {
        $overlapExists = AcademicYearEvaluationPeriod::query()
            ->where('academic_year_id', $academicYearId)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereNull('deleted_at')
            ->where(function ($query) use ($startDate, $endDate) {
                $query
                    ->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($nested) use ($startDate, $endDate) {
                        $nested
                            ->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'start_date' => [
                    __('validation/academic/academic-year-evaluation-period.messages.date_overlap'),
                ],
            ]);
        }
    }
}
