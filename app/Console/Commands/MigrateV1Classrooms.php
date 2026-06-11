<?php

namespace App\Console\Commands;

use App\Models\Academic\Classroom;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Classrooms extends Command
{
    protected $signature = 'migrate:v1-classrooms {--fresh : Delete migrated classrooms before importing again}';

    protected $description = 'Migrate classrooms from v1 ac.facilities to v2 classrooms';

    public function handle(): int
    {
        $this->info('Starting classrooms migration from v1 ac.facilities...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'classroom')
                ->pluck('new_id');

            Classroom::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'classroom')
                ->delete();
        }

        $facilities = DB::connection('pgsql_v1')
            ->table('ac.facilities')
            ->orderBy('name')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($facilities as $oldFacility) {
                $tenantId = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $oldFacility->company_id)
                    ->value('new_id');

                if (! $tenantId) {
                    $failed++;
                    continue;
                }

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'classroom')
                        ->where('old_id', $oldFacility->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $code = trim((string) $oldFacility->code);
                $name = trim((string) $oldFacility->name);

                if ($code === '') {
                    $code = 'CLS-' . substr((string) $oldFacility->id, 0, 8);
                }

                if ($name === '') {
                    $name = $code;
                }

                $existing = Classroom::query()
                    ->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($code, $name) {
                        $query->where('code', $code)
                            ->orWhere('name', $name);
                    })
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'classroom',
                        'old_id' => $oldFacility->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'old_company_id' => $oldFacility->company_id,
                            'old_code' => $oldFacility->code,
                            'old_name' => $oldFacility->name,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $classroom = Classroom::query()->create([
                    'tenant_id' => $tenantId,
                    'code' => $code,
                    'name' => $name,
                    'capacity' => $oldFacility->capacity !== null ? (int) $oldFacility->capacity : null,
                    'location' => $oldFacility->address ?? null,
                    'description' => $oldFacility->description ?? null,
                    'is_active' => (bool) ($oldFacility->is_active ?? true),
                    'created_at' => $oldFacility->created_at ?? now(),
                    'updated_at' => $oldFacility->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'classroom',
                    'old_id' => $oldFacility->id,
                    'new_id' => $classroom->id,
                    'metadata' => [
                        'old_company_id' => $oldFacility->company_id,
                        'old_code' => $oldFacility->code,
                        'old_name' => $oldFacility->name,
                        'old_address' => $oldFacility->address ?? null,
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
