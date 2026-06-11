<?php

namespace App\Console\Commands;

use App\Models\Academic\Specialty;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Specialties extends Command
{
    protected $signature = 'migrate:v1-specialties {--fresh : Delete migrated specialties before importing again}';

    protected $description = 'Migrate specialties from v1 ac.specialties to v2 specialties';

    public function handle(): int
    {
        $this->info('Starting specialties migration from v1...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'specialty')
                ->pluck('new_id');

            Specialty::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'specialty')
                ->delete();
        }

        $specialties = DB::connection('pgsql_v1')
            ->table('ac.specialties')
            ->orderBy('name')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($specialties as $oldSpecialty) {
                $tenantId = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $oldSpecialty->company_id)
                    ->value('new_id');

                if (! $tenantId) {
                    $failed++;
                    continue;
                }

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'specialty')
                        ->where('old_id', $oldSpecialty->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $code = $this->makeCode($oldSpecialty->name);

                $existing = Specialty::query()
                    ->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($code, $oldSpecialty) {
                        $query->where('code', $code)
                            ->orWhere('name', $oldSpecialty->name);
                    })
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'specialty',
                        'old_id' => $oldSpecialty->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'old_company_id' => $oldSpecialty->company_id,
                            'old_name' => $oldSpecialty->name,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $specialty = Specialty::query()->create([
                    'tenant_id' => $tenantId,
                    'code' => $code,
                    'name' => $oldSpecialty->name,
                    'description' => $oldSpecialty->description ?? null,
                    'is_active' => (bool) ($oldSpecialty->is_active ?? true),
                    'created_at' => $oldSpecialty->created_at ?? now(),
                    'updated_at' => $oldSpecialty->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'specialty',
                    'old_id' => $oldSpecialty->id,
                    'new_id' => $specialty->id,
                    'metadata' => [
                        'old_company_id' => $oldSpecialty->company_id,
                        'old_name' => $oldSpecialty->name,
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

    private function makeCode(string $name): string
    {
        return str($name)
            ->upper()
            ->replace(' ', '_')
            ->replace('-', '_')
            ->toString();
    }
}
