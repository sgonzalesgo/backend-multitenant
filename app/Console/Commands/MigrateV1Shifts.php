<?php

namespace App\Console\Commands;

use App\Models\Academic\Shift;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Shifts extends Command
{
    protected $signature = 'migrate:v1-shifts {--fresh : Delete migrated shifts before importing again}';

    protected $description = 'Migrate shifts from v1 ac.sections to v2 shifts';

    public function handle(): int
    {
        $this->info('Starting shifts migration from v1 ac.sections...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'shift')
                ->pluck('new_id');

            Shift::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'shift')
                ->delete();
        }

        $sections = DB::connection('pgsql_v1')
            ->table('ac.sections')
            ->orderBy('name')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($sections as $oldSection) {
                $tenantId = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $oldSection->company_id)
                    ->value('new_id');

                if (! $tenantId) {
                    $failed++;
                    continue;
                }

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'shift')
                        ->where('old_id', $oldSection->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $existing = Shift::query()
                    ->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($oldSection) {
                        $query->where('code', trim($oldSection->code))
                            ->orWhere('name', trim($oldSection->name));
                    })
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'shift',
                        'old_id' => $oldSection->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'old_table' => 'ac.sections',
                            'old_company_id' => $oldSection->company_id,
                            'old_code' => $oldSection->code,
                            'old_name' => $oldSection->name,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $shift = Shift::query()->create([
                    'tenant_id' => $tenantId,
                    'code' => trim($oldSection->code),
                    'name' => trim($oldSection->name),
                    'description' => $oldSection->description ?? null,
                    'is_active' => (bool) ($oldSection->is_active ?? true),
                    'created_at' => $oldSection->created_at ?? now(),
                    'updated_at' => $oldSection->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'shift',
                    'old_id' => $oldSection->id,
                    'new_id' => $shift->id,
                    'metadata' => [
                        'old_table' => 'ac.sections',
                        'old_company_id' => $oldSection->company_id,
                        'old_code' => $oldSection->code,
                        'old_name' => $oldSection->name,
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
