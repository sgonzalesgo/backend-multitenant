<?php

namespace App\Console\Commands;

use App\Models\Academic\Modality;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Modalities extends Command
{
    protected $signature = 'migrate:v1-modalities {--fresh : Delete migrated modalities before importing again}';

    protected $description = 'Migrate modalities from v1 ac.modalities to v2 modalities';

    public function handle(): int
    {
        $this->info('Starting modalities migration from v1...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'modality')
                ->pluck('new_id');

            Modality::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'modality')
                ->delete();
        }

        $modalities = DB::connection('pgsql_v1')
            ->table('ac.modalities')
            ->orderBy('name')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($modalities as $oldModality) {
                $tenantId = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $oldModality->company_id)
                    ->value('new_id');

                if (! $tenantId) {
                    $failed++;
                    continue;
                }

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'modality')
                        ->where('old_id', $oldModality->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $existing = Modality::query()
                    ->where('tenant_id', $tenantId)
                    ->where('code', $oldModality->code)
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'modality',
                        'old_id' => $oldModality->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'old_company_id' => $oldModality->company_id,
                            'old_code' => $oldModality->code,
                            'old_name' => $oldModality->name,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $modality = Modality::query()->create([
                    'tenant_id' => $tenantId,
                    'code' => trim($oldModality->code),
                    'name' => trim($oldModality->name),
                    'description' => $oldModality->description ?? null,
                    'is_active' => (bool) ($oldModality->is_active ?? true),
                    'created_at' => $oldModality->created_at ?? now(),
                    'updated_at' => $oldModality->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'modality',
                    'old_id' => $oldModality->id,
                    'new_id' => $modality->id,
                    'metadata' => [
                        'old_company_id' => $oldModality->company_id,
                        'old_code' => $oldModality->code,
                        'old_name' => $oldModality->name,
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
