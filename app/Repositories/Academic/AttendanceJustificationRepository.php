<?php

namespace App\Repositories\Academic;

use App\Models\Academic\AttendanceJustification;
use App\Models\Academic\AttendanceRecord;
use App\Models\Academic\AttendanceSession;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class AttendanceJustificationRepository
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

        $token = $user->token();

        return $token?->tenant_id ? (string) $token->tenant_id : null;
    }

    /**
     * @throws ValidationException
     */
    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.attendance_justification.php.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    public function index(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->requireTenantId();

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return AttendanceJustification::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->when(Arr::get($filters, 'status'), fn ($query, $value) => $query->where('status', $value))
            ->when(Arr::get($filters, 'justification_type'), fn ($query, $value) => $query->where('justification_type', $value))
            ->when(Arr::get($filters, 'student_id'), fn ($query, $value) => $query->where('student_id', $value))
            ->when(Arr::get($filters, 'person_id'), fn ($query, $value) => $query->where('person_id', $value))
            ->when(Arr::get($filters, 'attendance_session_id'), fn ($query, $value) => $query->where('attendance_session_id', $value))
            ->when(Arr::get($filters, 'attendance_record_id'), fn ($query, $value) => $query->where('attendance_record_id', $value))
            ->when(Arr::get($filters, 'from_date'), function ($query, $value) {
                $query->whereHas('attendanceSession', function ($q) use ($value) {
                    $q->whereDate('attendance_date', '>=', $value);
                });
            })
            ->when(Arr::get($filters, 'to_date'), function ($query, $value) {
                $query->whereHas('attendanceSession', function ($q) use ($value) {
                    $q->whereDate('attendance_date', '<=', $value);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @throws ValidationException
     */
    public function store(array $data): AttendanceJustification
    {
        $tenantId = $this->requireTenantId();

        $record = AttendanceRecord::query()
            ->with('attendanceSession')
            ->where('tenant_id', $tenantId)
            ->where('id', Arr::get($data, 'attendance_record_id'))
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'attendance_record_id' => __('messages.attendance_justification.php.record_not_found'),
            ]);
        }

        if ($record->status !== 'absent') {
            throw ValidationException::withMessages([
                'attendance_record_id' => __('messages.attendance_justification.php.record_must_be_absent'),
            ]);
        }

        if ($record->justification) {
            throw ValidationException::withMessages([
                'attendance_record_id' => __('messages.attendance_justification.php.already_exists'),
            ]);
        }

        $documentPath = null;

        try {
            if (Arr::has($data, 'document') && $data['document']) {
                $documentPath = $data['document']->store(
                    "attendance-justifications/{$tenantId}",
                    'public'
                );
            }

            return DB::transaction(function () use ($tenantId, $record, $data, $documentPath) {
                $justification = AttendanceJustification::query()->create([
                    'tenant_id' => $tenantId,
                    'attendance_record_id' => $record->id,
                    'attendance_session_id' => $record->attendance_session_id,
                    'student_id' => $record->student_id,
                    'person_id' => $record->person_id,
                    'requested_by' => auth()->id(),
                    'reviewed_by' => null,
                    'justification_type' => Arr::get($data, 'justification_type', 'other'),
                    'reason' => Arr::get($data, 'reason'),
                    'document_path' => $documentPath,
                    'status' => 'pending',
                    'reviewed_at' => null,
                    'review_observation' => null,
                ]);

                $record->update([
                    'requires_justification' => true,
                    'justification_status' => 'pending',
                    'justified_at' => null,
                ]);

                return $justification->refresh()->load($this->relations());
            });
        } catch (\Throwable $exception) {
            if ($documentPath) {
                Storage::disk('public')->delete($documentPath);
            }

            throw $exception;
        }
    }

    public function approve(AttendanceJustification $justification, array $data = []): AttendanceJustification
    {
        $tenantId = $this->requireTenantId();

        if ((string) $justification->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($justification->status === 'approved') {
            return $justification->refresh()->load($this->relations());
        }

        return DB::transaction(function () use ($justification, $data) {
            $justification->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_observation' => Arr::get($data, 'review_observation'),
            ]);

            $record = $justification->attendanceRecord;

            if ($record) {
                $record->update([
                    'status' => 'excused',
                    'requires_justification' => false,
                    'justification_status' => 'approved',
                    'justified_at' => now(),
                ]);
            }

            return $justification->refresh()->load($this->relations());
        });
    }

    public function reject(AttendanceJustification $justification, array $data = []): AttendanceJustification
    {
        $tenantId = $this->requireTenantId();

        if ((string) $justification->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($justification->status === 'rejected') {
            return $justification->refresh()->load($this->relations());
        }

        return DB::transaction(function () use ($justification, $data) {
            $justification->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_observation' => Arr::get($data, 'review_observation'),
            ]);

            $record = $justification->attendanceRecord;

            if ($record) {
                $record->update([
                    'status' => 'absent',
                    'requires_justification' => true,
                    'justification_status' => 'rejected',
                    'justified_at' => null,
                ]);
            }

            return $justification->refresh()->load($this->relations());
        });
    }

    public function delete(AttendanceJustification $justification): void
    {
        $tenantId = $this->requireTenantId();

        if ((string) $justification->tenant_id !== $tenantId) {
            abort(404);
        }

        DB::transaction(function () use ($justification) {
            $record = $justification->attendanceRecord;

            if ($record && $record->status === 'excused') {
                $record->update([
                    'status' => 'absent',
                    'requires_justification' => true,
                    'justification_status' => 'pending',
                    'justified_at' => null,
                ]);
            }

            $documentPath = $justification->document_path;

            $justification->delete();

            if ($documentPath) {
                Storage::disk('public')->delete($documentPath);
            }
        });
    }

    public function pendingRecords(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->requireTenantId();

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return AttendanceRecord::query()
            ->with([
                'attendanceSession:id,academic_year_id,evaluation_period_id,course_id,specialty_id,parallel_id,modality_id,shift_id,subject_id,instructor_id,attendance_date,status',
                'attendanceSession.academicYear:id,name',
                'attendanceSession.evaluationPeriod:id,code,name,start_date,end_date',
                'attendanceSession.course:id,code,name',
                'attendanceSession.specialty:id,code,name',
                'attendanceSession.parallel:id,code,name',
                'attendanceSession.modality:id,code,name',
                'attendanceSession.shift:id,code,name',
                'attendanceSession.subject:id,code,name',
                'attendanceSession.instructor:id,person_id',
                'attendanceSession.instructor.person:id,full_name,email,photo',
                'student:id,person_id,student_code,status',
                'person:id,full_name,email,legal_id,photo',
                'justification:id,attendance_record_id,status,justification_type,reason,document_path,reviewed_at,review_observation',
            ])
            ->where('tenant_id', $tenantId)
            ->where('status', 'absent')
            ->where('requires_justification', true)
            ->whereIn('justification_status', ['pending', 'rejected'])
            ->whereDoesntHave('justification', function ($query) {
                $query->whereIn('status', ['pending', 'approved']);
            })
            ->when(Arr::get($filters, 'student_id'), fn ($query, $value) => $query->where('student_id', $value))
            ->when(Arr::get($filters, 'person_id'), fn ($query, $value) => $query->where('person_id', $value))
            ->when(Arr::get($filters, 'academic_year_id'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->where('academic_year_id', $value));
            })
            ->when(Arr::get($filters, 'evaluation_period_id'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->where('evaluation_period_id', $value));
            })
            ->when(Arr::get($filters, 'course_id'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->where('course_id', $value));
            })
            ->when(Arr::get($filters, 'specialty_id'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->where('specialty_id', $value));
            })
            ->when(Arr::get($filters, 'parallel_id'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->where('parallel_id', $value));
            })
            ->when(Arr::get($filters, 'modality_id'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->where('modality_id', $value));
            })
            ->when(Arr::get($filters, 'shift_id'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->where('shift_id', $value));
            })
            ->when(Arr::get($filters, 'subject_id'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->where('subject_id', $value));
            })
            ->when(Arr::get($filters, 'instructor_id'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->where('instructor_id', $value));
            })
            ->when(Arr::get($filters, 'from_date'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->whereDate('attendance_date', '>=', $value));
            })
            ->when(Arr::get($filters, 'to_date'), function ($query, $value) {
                $query->whereHas('attendanceSession', fn ($q) => $q->whereDate('attendance_date', '<=', $value));
            })
            ->orderByDesc(
                AttendanceSession::query()
                    ->select('attendance_date')
                    ->whereColumn('attendance_sessions.id', 'attendance_records.attendance_session_id')
                    ->limit(1)
            )
            ->paginate($perPage);
    }

    /**
     * @throws \Throwable
     * @throws ValidationException
     */
    public function uploadDocument(AttendanceJustification $justification, array $data): AttendanceJustification
    {
        $tenantId = $this->requireTenantId();

        if ((string) $justification->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($justification->status === 'approved') {
            throw ValidationException::withMessages([
                'document' => __('messages.attendance_justification.php.approved_cannot_be_modified'),
            ]);
        }

        $oldDocumentPath = $justification->document_path;
        $newDocumentPath = null;

        try {
            $newDocumentPath = $data['document']->store(
                "attendance-justifications/{$tenantId}",
                'public'
            );

            DB::transaction(function () use ($justification, $newDocumentPath) {
                $justification->update([
                    'document_path' => $newDocumentPath,
                    'status' => 'pending',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_observation' => null,
                ]);

                $record = $justification->attendanceRecord;

                if ($record) {
                    $record->update([
                        'status' => 'absent',
                        'requires_justification' => true,
                        'justification_status' => 'pending',
                        'justified_at' => null,
                    ]);
                }
            });

            if ($oldDocumentPath) {
                Storage::disk('public')->delete($oldDocumentPath);
            }

            return $justification->refresh()->load($this->relations());
        } catch (\Throwable $exception) {
            if ($newDocumentPath) {
                Storage::disk('public')->delete($newDocumentPath);
            }

            throw $exception;
        }
    }

    protected function relations(): array
    {
        return [
            'attendanceRecord:id,attendance_session_id,enrollment_id,student_id,person_id,status,requires_justification,justification_status,justified_at',
            'attendanceSession:id,academic_year_id,evaluation_period_id,course_id,specialty_id,parallel_id,modality_id,shift_id,subject_id,instructor_id,attendance_date,status',
            'attendanceSession.academicYear:id,name',
            'attendanceSession.evaluationPeriod:id,code,name,start_date,end_date',
            'attendanceSession.course:id,code,name',
            'attendanceSession.specialty:id,code,name',
            'attendanceSession.parallel:id,code,name',
            'attendanceSession.modality:id,code,name',
            'attendanceSession.shift:id,code,name',
            'attendanceSession.subject:id,code,name',
            'attendanceSession.instructor:id,person_id',
            'attendanceSession.instructor.person:id,full_name,email,photo',
            'student:id,person_id,student_code,status',
            'person:id,full_name,email,legal_id,photo',
            'requestedBy:id,name,email',
            'reviewedBy:id,name,email',
        ];
    }
}
