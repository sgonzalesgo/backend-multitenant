<?php

namespace App\Console\Commands;

use App\Models\Academic\LegalRepresentative;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1LegalRepresentatives extends Command
{
    protected $signature = 'migrate:v1-legal-representatives {--fresh : Delete migrated legal representatives before importing again}';

    protected $description = 'Migrate legal representatives from v1 ac.representatives to v2 legal_representatives';

    public function handle(): int
    {
        $this->info('Starting legal representatives migration from v1...');

        if ($this->option('fresh')) {
            $this->warn('Fresh option enabled. Deleting previous legal representative mappings and records...');

            $ids = MigrationIdMap::query()
                ->where('entity', 'legal_representative')
                ->pluck('new_id');

            LegalRepresentative::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'legal_representative')
                ->delete();
        }

        $total = DB::connection('pgsql_v1')
            ->table('ac.representatives')
            ->count();

        $this->info("Found {$total} representatives in v1.");

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::connection('pgsql_v1')
            ->table('ac.representatives')
            ->orderBy('id')
            ->chunk(500, function ($representatives) use (&$created, &$skipped, &$failed) {
                DB::beginTransaction();

                try {
                    foreach ($representatives as $oldRepresentative) {
                        $existingMap = MigrationIdMap::query()
                            ->where('entity', 'legal_representative')
                            ->where('old_id', $oldRepresentative->id)
                            ->first();

                        if ($existingMap) {
                            $skipped++;
                            continue;
                        }

                        $tenantId = MigrationIdMap::query()
                            ->where('entity', 'tenant')
                            ->where('old_id', $oldRepresentative->company_id)
                            ->value('new_id');

                        $personId = MigrationIdMap::query()
                            ->where('entity', 'person')
                            ->where('old_id', $oldRepresentative->person_id)
                            ->value('new_id');

                        if (! $tenantId || ! $personId) {
                            $failed++;
                            continue;
                        }

                        $legalRepresentative = LegalRepresentative::query()->create([
                            'tenant_id' => $tenantId,
                            'person_id' => $personId,
                            'status' => ($oldRepresentative->is_active ?? true) ? 'active' : 'inactive',
                            'notes' => null,
                            'created_at' => $oldRepresentative->created_at ?? now(),
                            'updated_at' => $oldRepresentative->updated_at ?? now(),
                        ]);

                        MigrationIdMap::query()->create([
                            'entity' => 'legal_representative',
                            'old_id' => $oldRepresentative->id,
                            'new_id' => $legalRepresentative->id,
                            'metadata' => [
                                'old_person_id' => $oldRepresentative->person_id,
                                'old_company_id' => $oldRepresentative->company_id,
                            ],
                        ]);

                        $created++;
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
}
