<?php

namespace App\Console\Commands;

use App\Models\Academic\StudentLegalRepresentative;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1StudentLegalRepresentatives extends Command
{
    protected $signature = 'migrate:v1-student-legal-representatives {--fresh : Delete migrated relationships before importing again}';

    protected $description = 'Migrate student legal representative relationships from v1 ac.representatives to v2 student_legal_representatives';

    public function handle(): int
    {
        $this->info('Starting student legal representative relationships migration from v1...');

        if ($this->option('fresh')) {
            $this->warn('Fresh option enabled. Deleting previous relationships...');

            $ids = MigrationIdMap::query()
                ->where('entity', 'student_legal_representative')
                ->pluck('new_id');

            StudentLegalRepresentative::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'student_legal_representative')
                ->delete();
        }

        $total = DB::connection('pgsql_v1')
            ->table('ac.representatives')
            ->whereNotNull('students')
            ->count();

        $this->info("Found {$total} representatives with students in v1.");

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::connection('pgsql_v1')
            ->table('ac.representatives')
            ->whereNotNull('students')
            ->orderBy('id')
            ->chunk(500, function ($representatives) use (&$created, &$skipped, &$failed) {
                DB::beginTransaction();

                try {
                    foreach ($representatives as $oldRepresentative) {
                        $tenantId = MigrationIdMap::query()
                            ->where('entity', 'tenant')
                            ->where('old_id', $oldRepresentative->company_id)
                            ->value('new_id');

                        $legalRepresentativeId = MigrationIdMap::query()
                            ->where('entity', 'legal_representative')
                            ->where('old_id', $oldRepresentative->id)
                            ->value('new_id');

                        if (! $tenantId || ! $legalRepresentativeId) {
                            $failed++;
                            continue;
                        }

                        $oldStudentIds = json_decode($oldRepresentative->students, true);

                        if (! is_array($oldStudentIds) || empty($oldStudentIds)) {
                            $skipped++;
                            continue;
                        }

                        // Si viene como objeto JSON {"0":"id","2":"id"}, nos quedamos solo con los valores.
                        $oldStudentIds = array_values($oldStudentIds);

                        foreach ($oldStudentIds as $oldStudentId) {
                            $studentId = MigrationIdMap::query()
                                ->where('entity', 'student')
                                ->where('old_id', $oldStudentId)
                                ->value('new_id');

                            if (! $studentId) {
                                $skipped++;

                                $this->warn("Skipped relationship: student mapping not found for old student_id {$oldStudentId}");

                                continue;
                            }

                            $relationshipType = $this->normalizeRelationshipType(
                                $oldRepresentative->relationship_type ?? null
                            );

                            $oldMapId = $oldRepresentative->id . '|' . $oldStudentId . '|' . $relationshipType;

                            $existingMap = MigrationIdMap::query()
                                ->where('entity', 'student_legal_representative')
                                ->where('old_id', $oldMapId)
                                ->first();

                            if ($existingMap) {
                                $skipped++;
                                continue;
                            }

                            $existingRelation = StudentLegalRepresentative::query()
                                ->where('tenant_id', $tenantId)
                                ->where('student_id', $studentId)
                                ->where('legal_representative_id', $legalRepresentativeId)
                                ->where('relationship_type', $relationshipType)
                                ->first();

                            if ($existingRelation) {
                                MigrationIdMap::query()->create([
                                    'entity' => 'student_legal_representative',
                                    'old_id' => $oldMapId,
                                    'new_id' => $existingRelation->id,
                                    'metadata' => [
                                        'matched_existing' => true,
                                        'old_representative_id' => $oldRepresentative->id,
                                        'old_student_id' => $oldStudentId,
                                    ],
                                ]);

                                $skipped++;
                                continue;
                            }

                            $relation = StudentLegalRepresentative::query()->create([
                                'tenant_id' => $tenantId,
                                'student_id' => $studentId,
                                'legal_representative_id' => $legalRepresentativeId,
                                'relationship_type' => $relationshipType,
                                'description' => null,
                                'is_billable' => (bool) ($oldRepresentative->is_billable ?? false),
                                'is_emergency_contact' => (bool) ($oldRepresentative->is_emergency_contact ?? false),
                                'created_at' => $oldRepresentative->created_at ?? now(),
                                'updated_at' => $oldRepresentative->updated_at ?? now(),
                            ]);

                            MigrationIdMap::query()->create([
                                'entity' => 'student_legal_representative',
                                'old_id' => $oldMapId,
                                'new_id' => $relation->id,
                                'metadata' => [
                                    'old_representative_id' => $oldRepresentative->id,
                                    'old_student_id' => $oldStudentId,
                                    'old_relationship_type' => $oldRepresentative->relationship_type,
                                ],
                            ]);

                            $created++;
                        }
                    }

                    DB::commit();

                    $this->line("Processed chunk. Created: {$created}, Skipped: {$skipped}, Failed: {$failed}");
                } catch (\Throwable $e) {
                    DB::rollBack();

                    $failed += count($representatives);

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

    private function normalizeRelationshipType(?string $value): string
    {
        $value = strtoupper(trim((string) $value));

        return $value !== '' ? $value : 'REPRESENTANTE';
    }
}
