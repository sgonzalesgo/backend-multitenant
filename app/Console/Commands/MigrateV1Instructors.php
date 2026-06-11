<?php

namespace App\Console\Commands;

use App\Models\Academic\Instructor;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Instructors extends Command
{
    protected $signature = 'migrate:v1-instructors {--fresh : Delete migrated instructors before importing again}';

    protected $description = 'Migrate instructors from v1 ac.instructors to v2 instructors';

    public function handle(): int
    {
        $this->info('Starting instructors migration from v1...');

        if ($this->option('fresh')) {
            $this->warn('Fresh option enabled. Deleting previous instructor mappings and records...');

            $ids = MigrationIdMap::query()
                ->where('entity', 'instructor')
                ->pluck('new_id');

            Instructor::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'instructor')
                ->delete();
        }

        $total = DB::connection('pgsql_v1')
            ->table('ac.instructors')
            ->count();

        $this->info("Found {$total} instructors in v1.");

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::connection('pgsql_v1')
            ->table('ac.instructors')
            ->orderBy('id')
            ->chunk(500, function ($instructors) use (&$created, &$skipped, &$failed) {
                DB::beginTransaction();

                try {
                    foreach ($instructors as $oldInstructor) {
                        $existingMap = MigrationIdMap::query()
                            ->where('entity', 'instructor')
                            ->where('old_id', $oldInstructor->id)
                            ->first();

                        if ($existingMap) {
                            $skipped++;
                            continue;
                        }

                        $tenantId = MigrationIdMap::query()
                            ->where('entity', 'tenant')
                            ->where('old_id', $oldInstructor->company_id)
                            ->value('new_id');

                        $personId = MigrationIdMap::query()
                            ->where('entity', 'person')
                            ->where('old_id', $oldInstructor->person_id)
                            ->value('new_id');

                        if (! $tenantId || ! $personId) {
                            $failed++;
                            continue;
                        }

                        $existingInstructor = Instructor::query()
                            ->where('person_id', $personId)
                            ->first();

                        if ($existingInstructor) {
                            MigrationIdMap::query()->create([
                                'entity' => 'instructor',
                                'old_id' => $oldInstructor->id,
                                'new_id' => $existingInstructor->id,
                                'metadata' => [
                                    'matched_existing' => true,
                                    'old_person_id' => $oldInstructor->person_id,
                                    'old_company_id' => $oldInstructor->company_id,
                                ],
                            ]);

                            $skipped++;
                            continue;
                        }

                        $instructor = Instructor::query()->create([
                            'tenant_id' => $tenantId,
                            'person_id' => $personId,
                            'department_id' => null,
                            'code' => $this->makeCode($oldInstructor->id, $created + 1),
                            'academic_title' => $oldInstructor->academic_title ?? null,
                            'academic_level' => $oldInstructor->academic_level ?? null,
                            'specialty' => null,
                            'status' => ($oldInstructor->is_active ?? true) ? 'active' : 'inactive',
                            'status_changed_at' => null,
                            'created_at' => $oldInstructor->created_at ?? now(),
                            'updated_at' => $oldInstructor->updated_at ?? now(),
                        ]);

                        MigrationIdMap::query()->create([
                            'entity' => 'instructor',
                            'old_id' => $oldInstructor->id,
                            'new_id' => $instructor->id,
                            'metadata' => [
                                'old_person_id' => $oldInstructor->person_id,
                                'old_company_id' => $oldInstructor->company_id,
                                'old_academic_title' => $oldInstructor->academic_title ?? null,
                                'old_academic_level' => $oldInstructor->academic_level ?? null,
                            ],
                        ]);

                        $created++;
                    }

                    DB::commit();

                    $this->line("Processed chunk. Created: {$created}, Skipped: {$skipped}, Failed: {$failed}");
                } catch (\Throwable $e) {
                    DB::rollBack();

                    $failed += count($instructors);

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

    private function makeCode(string $oldId, int $number): string
    {
        return 'I' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }
}
