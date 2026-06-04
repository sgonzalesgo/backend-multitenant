<?php

namespace App\Repositories\Academic\QuantitativeEvaluation;


use App\Models\Academic\Enrollment;
use App\Models\Academic\GradeComponent;
use App\Models\Academic\GradeRecord;
use App\Models\Academic\GradeSession;
use App\Models\Administration\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


class GradeSessionRepository
{
    protected function resolveCurrentTenantId(): ?string
    {
        if ($current = Tenant::current()) {
            return (string) $current->id;
        }

        $user = auth()->user();

        if (! $user || ! method_exists($user, 'token')) {
            return null;
        }

        return $user->token()?->tenant_id
            ? (string) $user->token()->tenant_id
            : null;
    }

    /**
     * @throws ValidationException
     */
    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.grade_session.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    public function index(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->requireTenantId();

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return GradeSession::query()
            ->with([
                'academicYear:id,name,code',
                'evaluationPeriod:id,name,code,start_date,end_date',
                'course:id,name,code',
                'specialty:id,name,code',
                'parallel:id,name,code',
                'modality:id,name,code',
                'shift:id,name,code',
                'subject:id,name,code',
                'instructor:id,person_id,academic_title',
                'instructor.person:id,full_name,legal_id,email,photo',
                'records.components.gradeComponent:id,component_type,is_required',
            ])
            ->where('tenant_id', $tenantId)
            ->when(
                Arr::get($filters, 'academic_year_id'),
                fn ($query, $value) => $query->where('academic_year_id', $value)
            )
            ->when(
                Arr::get($filters, 'evaluation_period_id'),
                fn ($query, $value) => $query->where('evaluation_period_id', $value)
            )
            ->when(
                Arr::get($filters, 'course_id'),
                fn ($query, $value) => $query->where('course_id', $value)
            )
            ->when(
                Arr::get($filters, 'parallel_id'),
                fn ($query, $value) => $query->where('parallel_id', $value)
            )
            ->when(
                Arr::get($filters, 'modality_id'),
                fn ($query, $value) => $query->where('modality_id', $value)
            )
            ->when(
                Arr::get($filters, 'shift_id'),
                fn ($query, $value) => $query->where('shift_id', $value)
            )
            ->when(
                Arr::get($filters, 'subject_id'),
                fn ($query, $value) => $query->where('subject_id', $value)
            )
            ->when(
                Arr::get($filters, 'instructor_id'),
                fn ($query, $value) => $query->where('instructor_id', $value)
            )
            ->when(
                Arr::has($filters, 'specialty_id'),
                fn ($query) => Arr::get($filters, 'specialty_id')
                    ? $query->where('specialty_id', Arr::get($filters, 'specialty_id'))
                    : $query->whereNull('specialty_id')
            )
            ->when(
                Arr::get($filters, 'status'),
                fn ($query, $value) => $query->where('status', $value)
            )
            ->when(
                Arr::get($filters, 'q'),
                function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->whereHas('course', fn ($sub) =>
                        $sub->where('name', 'like', "%{$value}%")
                            ->orWhere('code', 'like', "%{$value}%")
                        )
                            ->orWhereHas('parallel', fn ($sub) =>
                            $sub->where('name', 'like', "%{$value}%")
                                ->orWhere('code', 'like', "%{$value}%")
                            )
                            ->orWhereHas('subject', fn ($sub) =>
                            $sub->where('name', 'like', "%{$value}%")
                                ->orWhere('code', 'like', "%{$value}%")
                            )
                            ->orWhereHas('instructor.person', fn ($sub) =>
                            $sub->where('full_name', 'like', "%{$value}%")
                            );
                    });
                }
            )
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->through(function (GradeSession $session) {
                $recordsCount = $session->records->count();

                $completedRecordsCount = $session->records
                    ->filter(fn (GradeRecord $record) => $this->isRecordComplete($record))
                    ->count();

                $session->records_count = $recordsCount;
                $session->completed_records_count = $completedRecordsCount;

                $session->pending_records_count = max(
                    $recordsCount - $completedRecordsCount,
                    0
                );

                $session->progress_percentage = $recordsCount > 0
                    ? round(($completedRecordsCount / $recordsCount) * 100, 2)
                    : 0;

                return $session;
            });
    }

    /**
     * @throws ValidationException
     */
    public function openSession(array $data): GradeSession
    {
        $tenantId = $this->requireTenantId();

        $components = $this->getGradeComponents($tenantId, $data);

        if ($components->isEmpty()) {
            throw ValidationException::withMessages([
                'grade_components' => __('messages.grade_session.components_not_generated'),
            ]);
        }

        $enrollments = $this->getEnrollments($tenantId, $data);

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'students' => __('messages.grade_session.students_not_found'),
            ]);
        }

        return DB::transaction(function () use ($tenantId, $data, $components, $enrollments) {
            $session = GradeSession::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'academic_year_id' => Arr::get($data, 'academic_year_id'),
                    'evaluation_period_id' => Arr::get($data, 'evaluation_period_id'),
                    'course_id' => Arr::get($data, 'course_id'),
                    'specialty_id' => Arr::get($data, 'specialty_id'),
                    'parallel_id' => Arr::get($data, 'parallel_id'),
                    'modality_id' => Arr::get($data, 'modality_id'),
                    'shift_id' => Arr::get($data, 'shift_id'),
                    'subject_id' => Arr::get($data, 'subject_id'),
                    'instructor_id' => Arr::get($data, 'instructor_id'),
                ],
                [
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]
            );

            if ($session->status === 'closed') {
                throw ValidationException::withMessages([
                    'grade_session_id' => __('messages.grade_session.session_closed'),
                ]);
            }

            foreach ($enrollments as $enrollment) {
                $record = GradeRecord::query()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'grade_session_id' => $session->id,
                        'enrollment_id' => $enrollment->id,
                    ],
                    [
                        'student_id' => $enrollment->student_id,
                        'person_id' => $enrollment->student?->person_id,
                        'final_score' => null,
                        'final_status' => null,
                        'qualitative_grade' => null,
                    ]
                );

                foreach ($components as $component) {
                    $record->components()->firstOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'grade_component_id' => $component->id,
                        ],
                        [
                            'score' => null,
                            'qualitative_grade' => null,
                            'observation' => null,
                        ]
                    );
                }
            }

            return $this->show($session);
        });
    }

    public function show(GradeSession $session): GradeSession
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        return $session->load([
            'academicYear:id,name,code',
            'evaluationPeriod:id,name,code,start_date,end_date',
            'course:id,name,code',
            'specialty:id,name,code',
            'parallel:id,name,code',
            'modality:id,name,code',
            'shift:id,name,code',
            'subject:id,name,code,evaluation_type_id,is_average,is_behavior',
            'instructor:id,person_id',
            'instructor.person:id,full_name,legal_id,email',

            'records' => fn ($query) => $query
                ->join('persons', 'persons.id', '=', 'grade_records.person_id')
                ->select('grade_records.*')
                ->orderByRaw('UPPER(persons.full_name) ASC'),

            'records.student:id,person_id,student_code,status',
            'records.person:id,full_name,legal_id,email,photo',

            'records.components' => fn ($query) => $query
                ->join('grade_components', 'grade_components.id', '=', 'grade_record_components.grade_component_id')
                ->select('grade_record_components.*')
                ->orderBy('grade_components.default_order'),

            'records.components.gradeComponent:id,component_key,component_type,code,name,weight,max_score,default_order,is_required,is_system_calculated',
        ]);
    }

    protected function getGradeComponents(string $tenantId, array $data)
    {
        return GradeComponent::query()
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('evaluation_period_id', Arr::get($data, 'evaluation_period_id'))
            ->where('course_id', Arr::get($data, 'course_id'))
            ->where('parallel_id', Arr::get($data, 'parallel_id'))
            ->where('modality_id', Arr::get($data, 'modality_id'))
            ->where('shift_id', Arr::get($data, 'shift_id'))
            ->where('subject_id', Arr::get($data, 'subject_id'))
            ->when(
                Arr::get($data, 'specialty_id'),
                fn ($query, $value) => $query->where('specialty_id', $value),
                fn ($query) => $query->whereNull('specialty_id')
            )
            ->where('is_active', true)
            ->orderBy('default_order')
            ->get();
    }

    protected function getEnrollments(string $tenantId, array $data)
    {
        return Enrollment::query()
            ->select('enrollments.*')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('persons', 'persons.id', '=', 'students.person_id')
            ->with([
                'student:id,person_id,student_code,status',
                'student.person:id,full_name,legal_id,email,photo',
                'enrollmentStatus:id,code,name',
            ])
            ->where('enrollments.tenant_id', $tenantId)
            ->where('enrollments.academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('enrollments.course_id', Arr::get($data, 'course_id'))
            ->where('enrollments.parallel_id', Arr::get($data, 'parallel_id'))
            ->where('enrollments.modality_id', Arr::get($data, 'modality_id'))
            ->where('enrollments.shift_id', Arr::get($data, 'shift_id'))
            ->when(
                Arr::get($data, 'specialty_id'),
                fn ($query, $value) => $query->where('enrollments.specialty_id', $value),
                fn ($query) => $query->whereNull('enrollments.specialty_id')
            )
            ->whereHas('enrollmentStatus', fn ($query) => $query->where('code', 'active'))
            ->orderByRaw('UPPER(persons.full_name) ASC')
            ->get();
    }

    /**
     * @throws ValidationException
     */
    public function saveGrades(GradeSession $session, array $data): GradeSession
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($session->status === 'closed') {
            throw ValidationException::withMessages([
                'grade_session_id' => __('messages.grade_session.session_closed'),
            ]);
        }

        return DB::transaction(function () use ($session, $data) {
            foreach (Arr::get($data, 'records', []) as $recordData) {
                $record = GradeRecord::query()
                    ->where('id', Arr::get($recordData, 'grade_record_id'))
                    ->where('grade_session_id', $session->id)
                    ->first();

                if (! $record) {
                    continue;
                }

                foreach (Arr::get($recordData, 'components', []) as $componentData) {
                    $recordComponent = $record->components()
                        ->where('id', Arr::get($componentData, 'grade_record_component_id'))
                        ->first();

                    if (! $recordComponent) {
                        continue;
                    }

                    $recordComponent->update([
                        'score' => Arr::get($componentData, 'score'),
                        'qualitative_grade' => Arr::get($componentData, 'qualitative_grade'),
                        'observation' => Arr::get($componentData, 'observation'),
                    ]);
                }

                $this->recalculateRecord($record);
            }

            $this->updateSessionProgress($session);

            return $this->show($session->refresh());
        });
    }

    protected function recalculateRecord(GradeRecord $record): void
    {
        $record->load([
            'components.gradeComponent:id,component_key,component_type,weight,is_required',
        ]);

        $numericComponents = $record->components
            ->filter(fn ($component) =>
                $component->gradeComponent?->component_type === 'numeric'
            );

        $behaviorComponent = $record->components
            ->first(fn ($component) =>
                $component->gradeComponent?->component_type === 'behavior'
            );

        $finalScore = null;

        $formative100 = $numericComponents
            ->first(fn ($component) =>
                $component->gradeComponent?->component_key === 'FORMATIVE_100'
            );

        if ($formative100 && $formative100->score !== null) {
            $finalScore = (float) $formative100->score;
        }

        $formative70 = $numericComponents
            ->first(fn ($component) =>
                $component->gradeComponent?->component_key === 'FORMATIVE_70'
            );

        $summative30 = $numericComponents
            ->first(fn ($component) =>
                $component->gradeComponent?->component_key === 'SUMMATIVE_30'
            );

        if (
            $formative70 &&
            $summative30 &&
            $formative70->score !== null &&
            $summative30->score !== null
        ) {
            $finalScore =
                ((float) $formative70->score * 0.70) +
                ((float) $summative30->score * 0.30);
        }

        $record->update([
            'final_score' => $finalScore !== null ? round($finalScore, 2) : null,

            // No calculamos aprobado/reprobado todavía.
            // Eso debe hacerse luego con una regla académica institucional/anual.
            'final_status' => null,

            'qualitative_grade' => $behaviorComponent?->qualitative_grade,
        ]);
    }

    protected function updateSessionProgress(GradeSession $session): void
    {
        $session->load([
            'records.components.gradeComponent:id,component_type,is_required',
        ]);

        $totalRecords = $session->records->count();

        if ($totalRecords === 0) {
            $session->update([
                'status' => 'draft',
            ]);

            return;
        }

        $completedRecords = $session->records
            ->filter(fn (GradeRecord $record) => $this->isRecordComplete($record))
            ->count();

        $status = match (true) {
            $completedRecords === 0 => 'draft',
            $completedRecords < $totalRecords => 'in_progress',
            default => 'completed',
        };

        $session->update([
            'status' => $status,
        ]);
    }

    protected function isRecordComplete(GradeRecord $record): bool
    {
        $requiredComponents = $record->components
            ->filter(fn ($component) =>
            (bool) $component->gradeComponent?->is_required
            );

        if ($requiredComponents->isEmpty()) {
            return false;
        }

        foreach ($requiredComponents as $component) {
            $type = $component->gradeComponent?->component_type;

            if ($type === 'numeric' && $component->score === null) {
                return false;
            }

            if ($type === 'behavior' && blank($component->qualitative_grade)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws ValidationException
     */
    public function closeSession(GradeSession $session): GradeSession
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($session->status !== 'completed') {
            throw ValidationException::withMessages([
                'grade_session_id' => __('messages.grade_session.session_not_completed'),
            ]);
        }

        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $this->show($session->refresh());
    }

    public function reopenSession(GradeSession $session): GradeSession
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($session->status !== 'closed') {
            throw ValidationException::withMessages([
                'grade_session_id' => __('messages.grade_session.session_not_closed'),
            ]);
        }

        $session->update([
            'status' => 'completed',
            'closed_at' => null,
        ]);

        return $this->show($session->refresh());
    }
}
