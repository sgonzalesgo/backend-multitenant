<?php

namespace App\Console\Commands;

use App\Models\Academic\Course;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Courses extends Command
{
    protected $signature = 'migrate:v1-courses {--fresh : Delete migrated courses before importing again}';

    protected $description = 'Migrate courses from v1 ac.courses to v2 courses';

    public function handle(): int
    {
        $this->info('Starting courses migration from v1...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'course')
                ->pluck('new_id');

            Course::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'course')
                ->delete();
        }

        $courses = DB::connection('pgsql_v1')
            ->table('ac.courses')
            ->orderBy('order_number')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($courses as $oldCourse) {
                $tenantId = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $oldCourse->company_id)
                    ->value('new_id');

                if (! $tenantId) {
                    $failed++;
                    continue;
                }

                $educationalLevelId = MigrationIdMap::query()
                    ->where('entity', 'educational_level')
                    ->where('old_id', $oldCourse->educational_level_id . '|' . $oldCourse->company_id)
                    ->value('new_id');

                if (! $educationalLevelId) {
                    $failed++;
                    continue;
                }

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'course')
                        ->where('old_id', $oldCourse->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $existing = Course::query()
                    ->where('tenant_id', $tenantId)
                    ->where('code', trim($oldCourse->code))
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'course',
                        'old_id' => $oldCourse->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'old_company_id' => $oldCourse->company_id,
                            'old_code' => $oldCourse->code,
                            'old_name' => $oldCourse->name,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $course = Course::query()->create([
                    'tenant_id' => $tenantId,
                    'educational_level_id' => $educationalLevelId,
                    'instructor_id' => null,
                    'level_number' => (int) ($oldCourse->order_number ?? 1),
                    'code' => trim($oldCourse->code),
                    'name' => trim($oldCourse->name),
                    'description' => $oldCourse->description ?? null,
                    'capacity' => (int) ($oldCourse->capacity ?? 0),
                    'credits' => null,
                    'theoretical_hours' => null,
                    'practical_hours' => null,
                    'total_hours' => null,
                    'status' => ($oldCourse->is_active ?? true) ? 'active' : 'inactive',
                    'notes' => null,
                    'created_at' => $oldCourse->created_at ?? now(),
                    'updated_at' => $oldCourse->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'course',
                    'old_id' => $oldCourse->id,
                    'new_id' => $course->id,
                    'metadata' => [
                        'old_company_id' => $oldCourse->company_id,
                        'old_educational_level_id' => $oldCourse->educational_level_id,
                        'old_code' => $oldCourse->code,
                        'old_name' => $oldCourse->name,
                        'old_type' => $oldCourse->type ?? null,
                        'old_type_report' => $oldCourse->type_report ?? null,
                    ],
                ]);

                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Created', 'Skipped', 'Failed'],
            [[$created, $skipped, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
