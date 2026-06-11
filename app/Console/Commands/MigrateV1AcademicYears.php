<?php

namespace App\Console\Commands;

use App\Models\Academic\AcademicYear;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1AcademicYears extends Command
{
    protected $signature = 'migrate:v1-academic-years {--fresh : Delete migrated academic years before importing again}';

    protected $description = 'Migrate academic years from v1 ac.academic_years to v2 academic_years';

    public function handle(): int
    {
        $this->info('Starting academic years migration from v1...');

        if ($this->option('fresh')) {
            $this->warn('Fresh option enabled. Deleting previous academic year mappings and records...');

            $ids = MigrationIdMap::query()
                ->where('entity', 'academic_year')
                ->pluck('new_id');

            AcademicYear::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'academic_year')
                ->delete();
        }

        $academicYears = DB::connection('pgsql_v1')
            ->table('ac.academic_years')
            ->orderBy('start_date')
            ->get();

        $this->info("Found {$academicYears->count()} academic years in v1.");

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($academicYears as $oldAcademicYear) {
                $existingMap = MigrationIdMap::query()
                    ->where('entity', 'academic_year')
                    ->where('old_id', $oldAcademicYear->id)
                    ->first();

                if ($existingMap) {
                    $skipped++;
                    continue;
                }

                $tenantId = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $oldAcademicYear->company_id)
                    ->value('new_id');

                if (! $tenantId) {
                    $this->warn("Skipped academic year {$oldAcademicYear->id}: tenant not found.");
                    $failed++;
                    continue;
                }

                $code = $this->makeCode($oldAcademicYear->name);

                $existing = AcademicYear::query()
                    ->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($code, $oldAcademicYear) {
                        $query->where('code', $code)
                            ->orWhere('name', $oldAcademicYear->name);
                    })
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'academic_year',
                        'old_id' => $oldAcademicYear->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'old_name' => $oldAcademicYear->name,
                            'old_company_id' => $oldAcademicYear->company_id,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $academicYear = AcademicYear::query()->create([
                    'tenant_id' => $tenantId,
                    'code' => $code,
                    'name' => $oldAcademicYear->name,
                    'description' => null,
                    'start_date' => $oldAcademicYear->start_date,
                    'end_date' => $oldAcademicYear->end_date,
                    'is_active' => (bool) ($oldAcademicYear->is_active ?? true),
                    'is_current' => (bool) ($oldAcademicYear->is_active ?? false),
                    'created_at' => $oldAcademicYear->created_at ?? now(),
                    'updated_at' => $oldAcademicYear->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'academic_year',
                    'old_id' => $oldAcademicYear->id,
                    'new_id' => $academicYear->id,
                    'metadata' => [
                        'old_name' => $oldAcademicYear->name,
                        'old_company_id' => $oldAcademicYear->company_id,
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
            ->replace(' ', '-')
            ->replace('--', '-')
            ->toString();
    }
}
