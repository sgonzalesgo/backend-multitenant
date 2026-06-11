<?php

namespace App\Console\Commands;

use App\Models\Academic\EducationalLevel;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1EducationalLevels extends Command
{
    protected $signature = 'migrate:v1-educational-levels {--fresh : Delete migrated educational levels before importing again}';

    protected $description = 'Migrate educational levels from v1 ac.educational_levels to v2 educational_levels';

    public function handle(): int
    {
        $this->info('Starting educational levels migration from v1...');

        if ($this->option('fresh')) {
            $this->warn('Fresh option enabled. Deleting previous educational level mappings and records...');

            $ids = MigrationIdMap::query()
                ->where('entity', 'educational_level')
                ->pluck('new_id');

            EducationalLevel::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'educational_level')
                ->delete();
        }

        $levels = DB::connection('pgsql_v1')
            ->table('ac.educational_levels')
            ->orderBy('order_number')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($levels as $oldLevel) {
                $companyIds = json_decode($oldLevel->companies, true);

                if (! is_array($companyIds) || empty($companyIds)) {
                    $failed++;
                    continue;
                }

                foreach ($companyIds as $oldCompanyId) {
                    $tenantId = MigrationIdMap::query()
                        ->where('entity', 'tenant')
                        ->where('old_id', $oldCompanyId)
                        ->value('new_id');

                    if (! $tenantId) {
                        $failed++;
                        continue;
                    }

                    $oldMapId = $oldLevel->id . '|' . $oldCompanyId;

                    if (
                        MigrationIdMap::query()
                            ->where('entity', 'educational_level')
                            ->where('old_id', $oldMapId)
                            ->exists()
                    ) {
                        $skipped++;
                        continue;
                    }

                    $code = $this->makeCode($oldLevel->name);

                    $existing = EducationalLevel::query()
                        ->where('tenant_id', $tenantId)
                        ->where('code', $code)
                        ->first();

                    if ($existing) {
                        MigrationIdMap::query()->create([
                            'entity' => 'educational_level',
                            'old_id' => $oldMapId,
                            'new_id' => $existing->id,
                            'metadata' => [
                                'matched_existing' => true,
                                'old_level_id' => $oldLevel->id,
                                'old_company_id' => $oldCompanyId,
                            ],
                        ]);

                        $skipped++;
                        continue;
                    }

                    $level = EducationalLevel::query()->create([
                        'tenant_id' => $tenantId,
                        'code' => $code,
                        'name' => $oldLevel->name,
                        'sort_order' => (int) $oldLevel->order_number,
                        'start_number' => (int) $oldLevel->start_course,
                        'end_number' => (int) $oldLevel->end_course,
                        'has_specialty' => (bool) $oldLevel->has_specialties,
                        'next_educational_level_id' => null,
                        'description' => $oldLevel->description ?? null,
                        'is_active' => (bool) ($oldLevel->is_active ?? true),
                        'created_at' => $oldLevel->created_at ?? now(),
                        'updated_at' => $oldLevel->updated_at ?? now(),
                    ]);

                    MigrationIdMap::query()->create([
                        'entity' => 'educational_level',
                        'old_id' => $oldMapId,
                        'new_id' => $level->id,
                        'metadata' => [
                            'old_level_id' => $oldLevel->id,
                            'old_company_id' => $oldCompanyId,
                            'old_name' => $oldLevel->name,
                        ],
                    ]);

                    $created++;
                }
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

    private function makeCode(string $name): string
    {
        return str($name)
            ->upper()
            ->replace(' ', '_')
            ->replace('-', '_')
            ->toString();
    }
}
