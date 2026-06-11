<?php

namespace App\Console\Commands;

use App\Models\Academic\Enrollment;
use App\Models\Academic\Modality;
use App\Models\Academic\Shift;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Enrollments extends Command
{
    protected $signature = 'migrate:v1-enrollments {--fresh : Delete migrated enrollments before importing again}';

    protected $description = 'Migrate enrollments from v1 ac.enrollments to v2 enrollments';

    public function handle(): int
    {
        $this->info('Starting enrollments migration from v1...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'enrollment')
                ->pluck('new_id');

            Enrollment::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'enrollment')
                ->delete();
        }

        $total = DB::connection('pgsql_v1')
            ->table('ac.enrollments')
            ->count();

        $this->info("Found {$total} enrollments in v1.");

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::connection('pgsql_v1')
            ->table('ac.enrollments')
            ->orderBy('created_at')
            ->chunk(500, function ($enrollments) use (&$created, &$skipped, &$failed) {
                DB::beginTransaction();

                try {
                    foreach ($enrollments as $oldEnrollment) {
                        if (
                            MigrationIdMap::query()
                                ->where('entity', 'enrollment')
                                ->where('old_id', $oldEnrollment->id)
                                ->exists()
                        ) {
                            $skipped++;
                            continue;
                        }

                        $tenantId = $this->mapId('tenant', $oldEnrollment->company_id);
                        $studentId = $this->mapId('student', $oldEnrollment->student_id);
                        $academicYearId = $this->mapId('academic_year', $oldEnrollment->academic_year_id);
                        $courseId = $this->mapId('course', $oldEnrollment->course_id);
                        $parallelId = $this->mapId('parallel', $oldEnrollment->parallel_id);
                        $specialtyId = $this->mapId('specialty', $oldEnrollment->specialty_id ?? null);
                        $statusId = $this->mapId('enrollment_status', $oldEnrollment->status_id ?? null);

                        if (! $tenantId || ! $studentId || ! $academicYearId) {
                            $failed++;
                            continue;
                        }

                        $modalityId = $this->resolvePresentialModalityId($tenantId);

                        if (! $modalityId) {
                            $this->warn("Skipped enrollment {$oldEnrollment->id}: PRESENCIAL modality not found for tenant {$tenantId}");
                            $failed++;
                            continue;
                        }

                        $shiftId = $this->resolveShiftId(
                            $tenantId,
                            $oldEnrollment->section ?? null
                        );

                        $enrollmentCode = $this->resolveEnrollmentCode(
                            $oldEnrollment->code ?? null,
                            $oldEnrollment->id
                        );

                        $existing = Enrollment::query()
                            ->where('tenant_id', $tenantId)
                            ->where('student_id', $studentId)
                            ->where('academic_year_id', $academicYearId)
                            ->where('course_id', $courseId)
                            ->where('parallel_id', $parallelId)
                            ->where('modality_id', $modalityId)
                            ->where('shift_id', $shiftId)
                            ->first();

                        if ($existing) {
                            MigrationIdMap::query()->create([
                                'entity' => 'enrollment',
                                'old_id' => $oldEnrollment->id,
                                'new_id' => $existing->id,
                                'metadata' => [
                                    'matched_existing' => true,
                                    'old_code' => $oldEnrollment->code,
                                ],
                            ]);

                            $skipped++;
                            continue;
                        }

                        $enrollment = Enrollment::query()->create([
                            'tenant_id' => $tenantId,
                            'enrollment_code' => $enrollmentCode,
                            'student_id' => $studentId,
                            'academic_year_id' => $academicYearId,
                            'course_id' => $courseId,
                            'specialty_id' => $specialtyId,
                            'parallel_id' => $parallelId,
                            'shift_id' => $shiftId,
                            'modality_id' => $modalityId,
                            'enrollment_status_id' => $statusId,
                            'assigned_user_id' => null,
                            'is_new' => (bool) ($oldEnrollment->is_new ?? false),
                            'is_conditional' => (bool) ($oldEnrollment->is_conditional ?? false),
                            'is_active' => (bool) ($oldEnrollment->is_active ?? true),
                            'observation' => $oldEnrollment->observation ?? null,
                            'submitted_at' => $oldEnrollment->created_at ?? null,
                            'student_email_sent_at' => null,
                            'representatives_email_sent_at' => null,
                            'created_at' => $oldEnrollment->created_at ?? now(),
                            'updated_at' => $oldEnrollment->updated_at ?? now(),
                        ]);

                        MigrationIdMap::query()->create([
                            'entity' => 'enrollment',
                            'old_id' => $oldEnrollment->id,
                            'new_id' => $enrollment->id,
                            'metadata' => [
                                'old_code' => $oldEnrollment->code,
                                'old_company_id' => $oldEnrollment->company_id,
                                'old_student_id' => $oldEnrollment->student_id,
                                'old_academic_year_id' => $oldEnrollment->academic_year_id,
                                'old_course_id' => $oldEnrollment->course_id,
                                'old_parallel_id' => $oldEnrollment->parallel_id,
                                'old_modality_id' => $oldEnrollment->modality_id ?? null,
                                'forced_modality_code' => 'PRE',
                                'old_specialty_id' => $oldEnrollment->specialty_id ?? null,
                                'old_section' => $oldEnrollment->section ?? null,
                                'old_status_id' => $oldEnrollment->status_id ?? null,
                            ],
                        ]);

                        $created++;
                    }

                    DB::commit();

                    $this->line("Processed chunk. Created: {$created}, Skipped: {$skipped}, Failed: {$failed}");
                } catch (\Throwable $e) {
                    DB::rollBack();

                    $failed += count($enrollments);

                    $this->error($e->getMessage());

                    return false;
                }

                return true;
            });

        $this->table(
            ['Created', 'Skipped', 'Failed'],
            [[$created, $skipped, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function mapId(string $entity, ?string $oldId): ?string
    {
        if (! $oldId) {
            return null;
        }

        return MigrationIdMap::query()
            ->where('entity', $entity)
            ->where('old_id', $oldId)
            ->value('new_id');
    }

    private function resolvePresentialModalityId(string $tenantId): ?string
    {
        return Modality::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->whereRaw('UPPER(code) = ?', ['PRE'])
                    ->orWhereRaw('UPPER(name) = ?', ['PRESENCIAL']);
            })
            ->value('id');
    }

    private function resolveShiftId(?string $tenantId, ?string $section): ?string
    {
        if (! $tenantId || ! $section) {
            return null;
        }

        $section = strtoupper(trim($section));

        $aliases = [
            'MATUTINO' => ['MATUTINO', 'MAT', 'M'],
            'MATUTINA' => ['MATUTINO', 'MATUTINA', 'MAT', 'M'],
            'VESPERTINO' => ['VESPERTINO', 'VESPERTINA', 'VES', 'V'],
            'VESPERTINA' => ['VESPERTINO', 'VESPERTINA', 'VES', 'V'],
            'NOCTURNO' => ['NOCTURNO', 'NOCTURNA', 'NOC', 'N'],
            'NOCTURNA' => ['NOCTURNO', 'NOCTURNA', 'NOC', 'N'],
        ];

        $values = $aliases[$section] ?? [$section];

        return Shift::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($values) {
                $query->whereIn(DB::raw('UPPER(name)'), $values)
                    ->orWhereIn(DB::raw('UPPER(code)'), $values);
            })
            ->value('id');
    }

    private function resolveEnrollmentCode(?string $code, string $oldId): string
    {
        $baseCode = trim((string) $code);

        if ($baseCode === '') {
            $baseCode = 'EN' . substr($oldId, 0, 8);
        }

        $candidate = $baseCode;
        $counter = 1;

        while (
        Enrollment::query()
            ->where('enrollment_code', $candidate)
            ->exists()
        ) {
            $candidate = $baseCode . '-' . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $candidate;
    }
}
