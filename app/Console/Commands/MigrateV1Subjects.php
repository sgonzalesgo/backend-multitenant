<?php

namespace App\Console\Commands;

use App\Models\Academic\Subject;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Subjects extends Command
{
    protected $signature = 'migrate:v1-subjects {--fresh : Delete migrated subjects before importing again}';

    protected $description = 'Migrate subjects from v1 ac.subjects to v2 subjects';

    public function handle(): int
    {
        $this->info('Starting subjects migration from v1...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'subject')
                ->pluck('new_id');

            Subject::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'subject')
                ->delete();
        }

        $subjects = DB::connection('pgsql_v1')
            ->table('ac.subjects')
            ->orderBy('name')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($subjects as $oldSubject) {
                $tenantId = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $oldSubject->company_id)
                    ->value('new_id');

                if (! $tenantId) {
                    $failed++;
                    continue;
                }

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'subject')
                        ->where('old_id', $oldSubject->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $code = trim((string) $oldSubject->code);

                if ($code === '') {
                    $code = 'SUB-' . substr((string) $oldSubject->id, 0, 6);
                }

                $existing = Subject::query()
                    ->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($code, $oldSubject) {
                        $query->where('code', $code)
                            ->orWhere('name', trim($oldSubject->name));
                    })
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'subject',
                        'old_id' => $oldSubject->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'old_company_id' => $oldSubject->company_id,
                            'old_code' => $oldSubject->code,
                            'old_name' => $oldSubject->name,
                            'old_type' => $oldSubject->type ?? null,
                            'old_type_subject' => $oldSubject->type_subject ?? null,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $subject = Subject::query()->create([
                    'tenant_id' => $tenantId,
                    'subject_type_id' => null,
                    'evaluation_type_id' => null,
                    'code' => $code,
                    'name' => trim($oldSubject->name),
                    'description' => $oldSubject->description ?? null,
                    'is_average' => (bool) ($oldSubject->is_average ?? true),
                    'is_behavior' => (bool) ($oldSubject->is_behavior ?? false),
                    'is_active' => (bool) ($oldSubject->is_active ?? true),
                    'created_at' => $oldSubject->created_at ?? now(),
                    'updated_at' => $oldSubject->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'subject',
                    'old_id' => $oldSubject->id,
                    'new_id' => $subject->id,
                    'metadata' => [
                        'old_company_id' => $oldSubject->company_id,
                        'old_code' => $oldSubject->code,
                        'old_name' => $oldSubject->name,
                        'old_type' => $oldSubject->type ?? null,
                        'old_type_subject' => $oldSubject->type_subject ?? null,
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
