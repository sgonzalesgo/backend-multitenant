<?php

namespace App\Console\Commands;

use App\Models\Academic\Student;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Students extends Command
{
    protected $signature = 'migrate:v1-students {--fresh : Delete migrated students before importing again}';

    protected $description = 'Migrate students from v1 ac.students to v2 students';

    public function handle(): int
    {
        $this->info('Starting students migration from v1...');

        if ($this->option('fresh')) {
            $this->warn('Fresh option enabled. Deleting previous student mappings and migrated students...');

            $studentIds = MigrationIdMap::query()
                ->where('entity', 'student')
                ->pluck('new_id');

            Student::query()
                ->whereIn('id', $studentIds)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'student')
                ->delete();
        }

        $total = DB::connection('pgsql_v1')
            ->table('ac.students')
            ->count();

        $this->info("Found {$total} students in v1.");

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::connection('pgsql_v1')
            ->table('ac.students')
            ->orderBy('id')
            ->chunk(500, function ($students) use (&$created, &$skipped, &$failed) {
                DB::beginTransaction();

                try {
                    foreach ($students as $oldStudent) {
                        $existingMap = MigrationIdMap::query()
                            ->where('entity', 'student')
                            ->where('old_id', $oldStudent->id)
                            ->first();

                        if ($existingMap) {
                            $skipped++;
                            continue;
                        }

                        $tenantId = MigrationIdMap::query()
                            ->where('entity', 'tenant')
                            ->where('old_id', $oldStudent->company_id)
                            ->value('new_id');

                        if (! $tenantId) {
                            $this->warn("Skipped student {$oldStudent->id}: tenant not found for company_id {$oldStudent->company_id}");
                            $failed++;
                            continue;
                        }

                        $personId = MigrationIdMap::query()
                            ->where('entity', 'person')
                            ->where('old_id', $oldStudent->person_id)
                            ->value('new_id');

                        if (! $personId) {
                            $this->warn("Skipped student {$oldStudent->id}: person not found for person_id {$oldStudent->person_id}");
                            $failed++;
                            continue;
                        }

                        $studentCode = trim((string) $oldStudent->code);

                        if ($studentCode === '') {
                            $studentCode = 'ST-' . substr((string) $oldStudent->id, 0, 8);
                        }

                        $existingStudent = Student::query()
                            ->where('tenant_id', $tenantId)
                            ->where(function ($query) use ($personId, $studentCode) {
                                $query->where('person_id', $personId)
                                    ->orWhere('student_code', $studentCode);
                            })
                            ->first();

                        if ($existingStudent) {
                            MigrationIdMap::query()->create([
                                'entity' => 'student',
                                'old_id' => $oldStudent->id,
                                'new_id' => $existingStudent->id,
                                'metadata' => [
                                    'matched_existing' => true,
                                    'old_code' => $oldStudent->code,
                                    'old_company_id' => $oldStudent->company_id,
                                    'old_person_id' => $oldStudent->person_id,
                                ],
                            ]);

                            $skipped++;
                            continue;
                        }

                        $student = Student::query()->create([
                            'tenant_id' => $tenantId,
                            'person_id' => $personId,
                            'student_code' => $studentCode,
                            'status' => ($oldStudent->is_active ?? true) ? 'active' : 'inactive',
                            'notes' => null,
                            'created_at' => $oldStudent->created_at ?? now(),
                            'updated_at' => $oldStudent->updated_at ?? now(),
                        ]);

                        MigrationIdMap::query()->create([
                            'entity' => 'student',
                            'old_id' => $oldStudent->id,
                            'new_id' => $student->id,
                            'metadata' => [
                                'old_code' => $oldStudent->code,
                                'old_company_id' => $oldStudent->company_id,
                                'old_person_id' => $oldStudent->person_id,
                            ],
                        ]);

                        $created++;
                    }

                    DB::commit();

                    $this->line("Processed chunk. Created: {$created}, Skipped: {$skipped}, Failed: {$failed}");
                } catch (\Throwable $e) {
                    DB::rollBack();

                    $failed += count($students);

                    $this->error($e->getMessage());

                    return false;
                }

                return true;
            });

        $this->newLine();

        $this->table(
            ['Created', 'Skipped', 'Failed'],
            [[$created, $skipped, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
