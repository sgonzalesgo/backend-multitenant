<?php

namespace App\Console\Commands;

use App\Models\Academic\Parallel;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Parallels extends Command
{
    protected $signature = 'migrate:v1-parallels {--fresh : Delete migrated parallels before importing again}';

    protected $description = 'Migrate parallels from v1 ac.parallels to v2 parallels';

    public function handle(): int
    {
        $this->info('Starting parallels migration from v1...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'parallel')
                ->pluck('new_id');

            Parallel::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'parallel')
                ->delete();
        }

        $parallels = DB::connection('pgsql_v1')
            ->table('ac.parallels')
            ->orderBy('order_number')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($parallels as $oldParallel) {
                $tenantId = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $oldParallel->company_id)
                    ->value('new_id');

                if (! $tenantId) {
                    $failed++;
                    continue;
                }

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'parallel')
                        ->where('old_id', $oldParallel->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $existing = Parallel::query()
                    ->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($oldParallel) {
                        $query->where('code', trim($oldParallel->code))
                            ->orWhere('name', trim($oldParallel->name));
                    })
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'parallel',
                        'old_id' => $oldParallel->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'old_company_id' => $oldParallel->company_id,
                            'old_code' => $oldParallel->code,
                            'old_name' => $oldParallel->name,
                            'old_order_number' => $oldParallel->order_number ?? null,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $parallel = Parallel::query()->create([
                    'tenant_id' => $tenantId,
                    'code' => trim($oldParallel->code),
                    'name' => trim($oldParallel->name),
                    'description' => $oldParallel->description ?? null,
                    'is_active' => (bool) ($oldParallel->is_active ?? true),
                    'created_at' => $oldParallel->created_at ?? now(),
                    'updated_at' => $oldParallel->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'parallel',
                    'old_id' => $oldParallel->id,
                    'new_id' => $parallel->id,
                    'metadata' => [
                        'old_company_id' => $oldParallel->company_id,
                        'old_code' => $oldParallel->code,
                        'old_name' => $oldParallel->name,
                        'old_order_number' => $oldParallel->order_number ?? null,
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
