<?php

namespace App\Repositories\Academic\QualitativeEvaluation;

use App\Models\Academic\Enrollment;
use App\Models\Academic\QualitativeEvaluationComponent;
use App\Models\Academic\QualitativeEvaluationRecord;
use App\Models\Academic\QualitativeEvaluationRecordSkill;
use App\Models\Academic\QualitativeEvaluationSession;
use App\Models\Administration\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Academic\EvaluationPeriod;
use Carbon\Carbon;

class QualitativeEvaluationSessionRepository
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
                'tenant' => __('messages.qualitative_evaluation_sessions.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    public function index(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->requireTenantId();

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return QualitativeEvaluationSession::query()
            ->with([
                'academicYear:id,name,code',
                'evaluationPeriod:id,name,code,start_date,end_date',
                'course:id,name,code',
                'specialty:id,name,code',
                'parallel:id,name,code',
                'modality:id,name,code',
                'shift:id,name,code',
                'subject:id,name,code',

                'records:id,qualitative_evaluation_session_id,student_id',
                'records.skills:id,qualitative_evaluation_record_id,qualitative_evaluation_component_id,value,observation',
                'records.skills.component:id,order,is_required',
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
                Arr::has($filters, 'specialty_id'),
                fn ($query) => Arr::get($filters, 'specialty_id')
                    ? $query->where('specialty_id', Arr::get($filters, 'specialty_id'))
                    : $query->whereNull('specialty_id')
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
                function ($query, $instructorId) use ($tenantId) {
                    $query->whereExists(function ($sub) use ($tenantId, $instructorId) {
                        $sub->selectRaw('1')
                            ->from('academic_schedule_frequencies as frequencies')
                            ->join(
                                'academic_schedules as schedules',
                                'schedules.id',
                                '=',
                                'frequencies.academic_schedule_id'
                            )
                            ->where('schedules.tenant_id', $tenantId)
                            ->whereColumn(
                                'schedules.academic_year_id',
                                'qualitative_evaluation_sessions.academic_year_id'
                            )
                            ->whereColumn(
                                'schedules.course_id',
                                'qualitative_evaluation_sessions.course_id'
                            )
                            ->whereColumn(
                                'schedules.parallel_id',
                                'qualitative_evaluation_sessions.parallel_id'
                            )
                            ->whereColumn(
                                'schedules.modality_id',
                                'qualitative_evaluation_sessions.modality_id'
                            )
                            ->whereColumn(
                                'schedules.shift_id',
                                'qualitative_evaluation_sessions.shift_id'
                            )
                            ->whereColumn(
                                'frequencies.subject_id',
                                'qualitative_evaluation_sessions.subject_id'
                            )
                            ->where('frequencies.instructor_id', $instructorId)
                            ->where(function ($q) {
                                $q->whereColumn(
                                    'schedules.specialty_id',
                                    'qualitative_evaluation_sessions.specialty_id'
                                )
                                    ->orWhere(function ($subQuery) {
                                        $subQuery
                                            ->whereNull('schedules.specialty_id')
                                            ->whereNull('qualitative_evaluation_sessions.specialty_id');
                                    });
                            });
                    });
                }
            )

            ->when(
                Arr::get($filters, 'status'),
                function ($query, $value) {
                    if ($value === 'closed') {
                        $query->where('is_closed', true);
                    }

                    if ($value === 'open') {
                        $query->where(function ($q) {
                            $q->where('is_closed', false)
                                ->orWhereNull('is_closed');
                        });
                    }
                }
            )

            ->when(
                Arr::get($filters, 'q'),
                function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->whereHas('course', function ($sub) use ($value) {
                            $sub->where('name', 'like', "%{$value}%")
                                ->orWhere('code', 'like', "%{$value}%");
                        })
                            ->orWhereHas('parallel', function ($sub) use ($value) {
                                $sub->where('name', 'like', "%{$value}%")
                                    ->orWhere('code', 'like', "%{$value}%");
                            })
                            ->orWhereHas('subject', function ($sub) use ($value) {
                                $sub->where('name', 'like', "%{$value}%")
                                    ->orWhere('code', 'like', "%{$value}%");
                            });
                    });
                }
            )

            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->through(function (QualitativeEvaluationSession $session) use ($tenantId) {
                $session = $this->appendQualitativeSessionProgress($session);

                $session->instructor = $this->resolveSessionInstructor($tenantId, $session);

                return $session;
            });
    }

    /**
     * @throws ValidationException
     */
    public function open(array $data): array
    {
        $tenantId = $this->requireTenantId();

        $components = $this->getComponents($tenantId, $data);

        if ($components->isEmpty()) {
            throw ValidationException::withMessages([
                'components' => __('messages.qualitative_evaluation_sessions.components_not_generated'),
            ]);
        }

        $enrollments = $this->getEnrollments($tenantId, $data);

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'students' => __('messages.qualitative_evaluation_sessions.students_not_found'),
            ]);
        }

        return DB::transaction(function () use ($data, $tenantId, $components, $enrollments) {
            $session = QualitativeEvaluationSession::query()->firstOrCreate(
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
                ],
                [
                    'name' => Arr::get($data, 'name', 'Evaluación cualitativa'),
                    'is_closed' => false,
                ]
            );

            foreach ($enrollments as $enrollment) {
                $record = QualitativeEvaluationRecord::query()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'qualitative_evaluation_session_id' => $session->id,
                        'student_id' => $enrollment->student_id,
                    ],
                    [
                        'enrollment_id' => $enrollment->id,
                    ]
                );

                foreach ($components as $component) {
                    QualitativeEvaluationRecordSkill::query()->firstOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'qualitative_evaluation_record_id' => $record->id,
                            'qualitative_evaluation_component_id' => $component->id,
                        ],
                        [
                            'value' => null,
                            'observation' => null,
                        ]
                    );
                }
            }

            return $this->matrix($session->refresh());
        });
    }

    public function find(QualitativeEvaluationSession $session): array
    {
        return $this->matrix($session);
    }

    /**
     * @throws ValidationException
     */
    public function save(array $data): array
    {
        $tenantId = $this->requireTenantId();

        $session = QualitativeEvaluationSession::query()
            ->where('tenant_id', $tenantId)
            ->where('id', Arr::get($data, 'qualitative_evaluation_session_id'))
            ->first();

        if (! $session) {
            throw ValidationException::withMessages([
                'qualitative_evaluation_session_id' => __('messages.qualitative_evaluation_sessions.not_found'),
            ]);
        }

        // esto lo habilitamos para que pueda gestionar cesiones de evaluación si queremos con base en periodos de evaluación
        $this->ensureGradesAreAllowed(
            $session->evaluation_period_id
        );

        if ($session->is_closed) {
            throw ValidationException::withMessages([
                'qualitative_evaluation_session_id' => __('messages.qualitative_evaluation_sessions.already_closed'),
            ]);
        }

        return DB::transaction(function () use ($data, $tenantId, $session) {
            foreach (Arr::get($data, 'records', []) as $recordPayload) {
                $record = QualitativeEvaluationRecord::query()
                    ->where('tenant_id', $tenantId)
                    ->where('qualitative_evaluation_session_id', $session->id)
                    ->where('student_id', Arr::get($recordPayload, 'student_id'))
                    ->first();

                if (! $record) {
                    continue;
                }

                foreach (Arr::get($recordPayload, 'skills', []) as $skillPayload) {
                    QualitativeEvaluationRecordSkill::query()
                        ->where('tenant_id', $tenantId)
                        ->where('qualitative_evaluation_record_id', $record->id)
                        ->where(
                            'qualitative_evaluation_component_id',
                            Arr::get($skillPayload, 'qualitative_evaluation_component_id')
                        )
                        ->update([
                            'value' => Arr::get($skillPayload, 'value'),
                            'observation' => Arr::get($skillPayload, 'observation'),
                        ]);
                }
            }

            return $this->matrix($session->refresh());
        });
    }

    public function close(QualitativeEvaluationSession $session): array
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        $session->is_closed = true;
        $session->save();

        return $this->matrix($session->refresh());
    }

    public function reopen(QualitativeEvaluationSession $session): array
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        // esto lo habilitamos para que pueda gestionar cesiones de evaluación si queremos con base en periodos de evaluación
        $this->ensureGradesAreAllowed(
            $session->evaluation_period_id
        );

        $session->is_closed = false;
        $session->save();

        return $this->matrix($session->refresh());
    }

    public function matrix(QualitativeEvaluationSession $session): array
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        $session->load($this->relations());

        $recordSkills = $session->records
            ->flatMap(fn ($record) => $record->skills)
            ->filter(fn ($recordSkill) => $recordSkill->component)
            ->sortBy(fn ($recordSkill) => $recordSkill->component->order);

        $components = $recordSkills
            ->pluck('component')
            ->unique('id')
            ->sortBy('order')
            ->values();

        $columns = $components->map(function ($component, int $index) {
            $skill = $component->skillDefinition;
            $area = $skill?->area;

            return [
                'field' => 'component_'.$component->id,
                'component_id' => $component->id,
                'order' => $component->order,
                'label' => $skill?->code ?: 'D'.($index + 1),
                'header' => $skill?->code ?: 'Destreza '.($index + 1),
                'area_id' => $area?->id,
                'area_code' => $area?->code,
                'area_name' => $area?->name,
                'skill_id' => $skill?->id,
                'skill_code' => $skill?->code,
                'skill_name' => $skill?->name,
                'skill_description' => $skill?->description,
                'is_required' => (bool) $component->is_required,
            ];
        })->values();

        $rows = $session->records
            ->sortBy(fn ($record) => strtoupper($record->student?->person?->full_name ?? ''))
            ->map(function ($record) use ($components) {
                $skillsByComponent = $record->skills->keyBy('qualitative_evaluation_component_id');

                $skills = [];

                foreach ($components as $component) {
                    $recordSkill = $skillsByComponent->get($component->id);

                    $skills[$component->id] = [
                        'record_skill_id' => $recordSkill?->id,
                        'qualitative_evaluation_component_id' => $component->id,
                        'value' => $recordSkill?->value,
                        'observation' => $recordSkill?->observation,
                    ];
                }

                return [
                    'id' => $record->id,
                    'record_id' => $record->id,
                    'student_id' => $record->student_id,
                    'enrollment_id' => $record->enrollment_id,
                    'student_code' => $record->student?->student_code,
                    'student_name' => $record->student?->person?->full_name,
                    'student_legal_id' => $record->student?->person?->legal_id,
                    'skills' => $skills,
                ];
            })
            ->values();

        return [
            'session' => [
                'id' => $session->id,
                'name' => $session->name,
                'is_closed' => (bool) $session->is_closed,
                'academic_year' => $session->academicYear,
                'evaluation_period' => $session->evaluationPeriod,
                'course' => $session->course,
                'specialty' => $session->specialty,
                'parallel' => $session->parallel,
                'modality' => $session->modality,
                'shift' => $session->shift,
                'subject' => $session->subject,
            ],
            'scale' => [
                ['value' => 'I', 'label' => 'Iniciado'],
                ['value' => 'EP', 'label' => 'En Proceso'],
                ['value' => 'A', 'label' => 'Adquirido'],
                ['value' => 'NE', 'label' => 'No Evaluado'],
            ],
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    protected function getComponents(string $tenantId, array $data): Collection
    {
        return QualitativeEvaluationComponent::query()
            ->with('skillDefinition.area')
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('evaluation_period_id', Arr::get($data, 'evaluation_period_id'))
            ->where('course_id', Arr::get($data, 'course_id'))
            ->where('parallel_id', Arr::get($data, 'parallel_id'))
            ->where('modality_id', Arr::get($data, 'modality_id'))
            ->where('shift_id', Arr::get($data, 'shift_id'))
            ->where('subject_id', Arr::get($data, 'subject_id'))
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    protected function getEnrollments(string $tenantId, array $data): Collection
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

    protected function appendQualitativeSessionProgress(QualitativeEvaluationSession $session): QualitativeEvaluationSession
    {
        $recordsCount = $session->records->count();

        $totalEvaluationsCount = 0;
        $completedEvaluationsCount = 0;

        foreach ($session->records as $record) {
            foreach ($record->skills as $skill) {
                $totalEvaluationsCount++;

                if (! blank($skill->value)) {
                    $completedEvaluationsCount++;
                }
            }
        }

        $pendingEvaluationsCount = max(
            $totalEvaluationsCount - $completedEvaluationsCount,
            0
        );

        $completedRecordsCount = $session->records
            ->filter(fn ($record) => $this->isQualitativeRecordComplete($record))
            ->count();

        $session->students_count = $recordsCount;
        $session->records_count = $recordsCount;

        $session->completed_records_count = $completedRecordsCount;
        $session->pending_records_count = max($recordsCount - $completedRecordsCount, 0);

        $session->total_evaluations_count = $totalEvaluationsCount;
        $session->completed_evaluations_count = $completedEvaluationsCount;
        $session->pending_evaluations_count = $pendingEvaluationsCount;

        $session->progress_percentage = $totalEvaluationsCount > 0
            ? round(($completedEvaluationsCount / $totalEvaluationsCount) * 100, 2)
            : 0;

        $session->status = $session->is_closed
            ? 'closed'
            : 'open';

        return $session;
    }

    protected function isQualitativeRecordComplete(QualitativeEvaluationRecord $record): bool
    {
        if ($record->skills->isEmpty()) {
            return false;
        }

        return $record->skills->every(function ($skill) {
            return ! blank($skill->value);
        });
    }

    protected function resolveSessionInstructor(string $tenantId, QualitativeEvaluationSession $session): ?array
    {
        $frequency = DB::table('academic_schedule_frequencies as frequencies')
            ->join('academic_schedules as schedules', 'schedules.id', '=', 'frequencies.academic_schedule_id')
            ->join('instructors', 'instructors.id', '=', 'frequencies.instructor_id')
            ->leftJoin('persons', 'persons.id', '=', 'instructors.person_id')
            ->where('schedules.tenant_id', $tenantId)
            ->where('schedules.academic_year_id', $session->academic_year_id)
            ->where('schedules.course_id', $session->course_id)
            ->where('schedules.parallel_id', $session->parallel_id)
            ->where('schedules.modality_id', $session->modality_id)
            ->where('schedules.shift_id', $session->shift_id)
            ->where('frequencies.subject_id', $session->subject_id)
            ->where(function ($query) use ($session) {
                if ($session->specialty_id) {
                    $query->where('schedules.specialty_id', $session->specialty_id);
                } else {
                    $query->whereNull('schedules.specialty_id');
                }
            })
            ->select([
                'instructors.id',
                'instructors.person_id',
                'instructors.academic_title',

                'persons.id as person_record_id',
                'persons.full_name',
                'persons.legal_id',
                'persons.email',
                'persons.photo',
            ])
            ->first();

        if (! $frequency) {
            return null;
        }

        return [
            'id' => $frequency->id,
            'person_id' => $frequency->person_id,
            'academic_title' => $frequency->academic_title,

            'person' => [
                'id' => $frequency->person_record_id,
                'full_name' => $frequency->full_name,
                'legal_id' => $frequency->legal_id,
                'email' => $frequency->email,
                'photo' => $frequency->photo,
            ],
        ];
    }

    /**
     * @throws ValidationException
     */
    protected function ensureGradesAreAllowed(?string $evaluationPeriodId): void
    {
        $user = auth()->user();

        if ($user && $user->can('Manage grades_outside_periods')) {
            return;
        }

        if (! $evaluationPeriodId) {
            throw ValidationException::withMessages([
                'evaluation_period_id' => __('messages.academic_context.evaluation_period_required'),
            ]);
        }

        $period = EvaluationPeriod::query()
            ->where('id', $evaluationPeriodId)
            ->first();

        if (! $period) {
            throw ValidationException::withMessages([
                'evaluation_period_id' => __('messages.academic_context.evaluation_period_not_found'),
            ]);
        }

        $today = Carbon::today();

        $isInsidePeriod = $period->start_date !== null
            && $period->end_date !== null
            && $period->start_date->lte($today)
            && $period->end_date->gte($today);

        if ($isInsidePeriod) {
            return;
        }

        if ($period->allow_grades) {
            return;
        }

        throw ValidationException::withMessages([
            'evaluation_period_id' => __('messages.academic_context.grades_not_allowed'),
        ]);
    }

    protected function relations(): array
    {
        return [
            'academicYear:id,name',
            'evaluationPeriod:id,name',
            'course:id,code,name',
            'specialty:id,code,name',
            'parallel:id,code,name',
            'modality:id,code,name',
            'shift:id,code,name',
            'subject:id,code,name',
            'records.student.person:id,full_name,legal_id,email,photo',
            'records.enrollment:id,student_id,enrollment_status_id',
            'records.skills.component.skillDefinition.area:id,tenant_id,code,name',
            'records.skills.component.skillDefinition:id,tenant_id,qualitative_evaluation_area_id,code,name,description,is_active',
        ];
    }
}
