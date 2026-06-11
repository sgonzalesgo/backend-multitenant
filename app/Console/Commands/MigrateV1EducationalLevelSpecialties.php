<?php

namespace App\Console\Commands;

use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateV1EducationalLevelSpecialties extends Command
{
    protected $signature = 'migrate:v1-educational-level-specialties {--fresh}';

    protected $description = 'Migrate educational level specialty pivot from v1 to v2';

    public function handle(): int
    {
        $this->info('Starting educational level specialties migration...');

        if ($this->option('fresh')) {
            DB::table('educational_level_specialty')->delete();

            MigrationIdMap::query()
                ->where('entity', 'educational_level_specialty')
                ->delete();
        }

        $levels = DB::connection('pgsql_v1')
            ->table('ac.educational_levels')
            ->where('has_specialties', true)
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($levels as $oldLevel) {
            $companyIds = json_decode($oldLevel->companies, true);

            if (! is_array($companyIds)) {
                $failed++;
                continue;
            }

            foreach ($companyIds as $oldCompanyId) {
                $educationalLevelId = MigrationIdMap::query()
                    ->where('entity', 'educational_level')
                    ->where('old_id', $oldLevel->id . '|' . $oldCompanyId)
                    ->value('new_id');

                if (! $educationalLevelId) {
                    $failed++;
                    continue;
                }

                $specialties = DB::connection('pgsql_v1')
                    ->table('ac.specialties')
                    ->where('company_id', $oldCompanyId)
                    ->get();

                foreach ($specialties as $oldSpecialty) {
                    $specialtyId = MigrationIdMap::query()
                        ->where('entity', 'specialty')
                        ->where('old_id', $oldSpecialty->id)
                        ->value('new_id');

                    $tenantId = MigrationIdMap::query()
                        ->where('entity', 'tenant')
                        ->where('old_id', $oldCompanyId)
                        ->value('new_id');

                    if (! $specialtyId || ! $tenantId) {
                        $failed++;
                        continue;
                    }

                    $exists = DB::table('educational_level_specialty')
                        ->where('tenant_id', $tenantId)
                        ->where('educational_level_id', $educationalLevelId)
                        ->where('specialty_id', $specialtyId)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    $id = (string) Str::uuid();

                    DB::table('educational_level_specialty')->insert([
                        'id' => $id,
                        'tenant_id' => $tenantId,
                        'educational_level_id' => $educationalLevelId,
                        'specialty_id' => $specialtyId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    MigrationIdMap::query()->create([
                        'entity' => 'educational_level_specialty',
                        'old_id' => $oldLevel->id . '|' . $oldSpecialty->id . '|' . $oldCompanyId,
                        'new_id' => $id,
                    ]);

                    $created++;
                }
            }
        }

        $this->table(
            ['Created', 'Skipped', 'Failed'],
            [[$created, $skipped, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
