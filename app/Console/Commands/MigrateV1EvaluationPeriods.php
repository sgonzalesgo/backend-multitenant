<?php

namespace App\Console\Commands;

use App\Models\Academic\EvaluationPeriod;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1EvaluationPeriods extends Command
{
    protected $signature = 'migrate:v1-evaluation-periods {--fresh : Delete migrated evaluation periods before importing again}';

    protected $description = 'Migrate evaluation periods from v1 ac.academic_periods to v2 evaluation_periods';

    public function handle(): int
    {
        $this->info('Starting evaluation periods migration from v1 ac.academic_periods...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'evaluation_period')
                ->pluck('new_id');

            EvaluationPeriod::query()
                ->whereIn('id', $ids)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'evaluation_period')
                ->delete();
        }

        $periods = DB::connection('pgsql_v1')
            ->table('ac.academic_periods')
            ->orderBy('start_date')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        $orderByAcademicYear = [];

        DB::beginTransaction();

        try {
            foreach ($periods as $oldPeriod) {
                $academicYearId = MigrationIdMap::query()
                    ->where('entity', 'academic_year')
                    ->where('old_id', $oldPeriod->academic_year_id)
                    ->value('new_id');

                if (! $academicYearId) {
                    $failed++;
                    continue;
                }

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'evaluation_period')
                        ->where('old_id', $oldPeriod->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $orderByAcademicYear[$academicYearId] =
                    ($orderByAcademicYear[$academicYearId] ?? 0) + 1;

                $code = $this->makeCode($oldPeriod->name);

                $existing = EvaluationPeriod::query()
                    ->where('academic_year_id', $academicYearId)
                    ->where(function ($query) use ($code, $oldPeriod) {
                        $query->where('code', $code)
                            ->orWhere('name', trim($oldPeriod->name));
                    })
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'evaluation_period',
                        'old_id' => $oldPeriod->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'old_academic_year_id' => $oldPeriod->academic_year_id,
                            'old_company_id' => $oldPeriod->company_id,
                            'old_name' => $oldPeriod->name,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $period = EvaluationPeriod::query()->create([
                    'academic_year_id' => $academicYearId,
                    'code' => $code,
                    'name' => trim($oldPeriod->name),
                    'description' => $oldPeriod->description ?? null,
                    'default_order' => $orderByAcademicYear[$academicYearId],
                    'start_date' => $oldPeriod->start_date,
                    'end_date' => $oldPeriod->end_date,
                    'created_at' => $oldPeriod->created_at ?? now(),
                    'updated_at' => $oldPeriod->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'evaluation_period',
                    'old_id' => $oldPeriod->id,
                    'new_id' => $period->id,
                    'metadata' => [
                        'old_academic_year_id' => $oldPeriod->academic_year_id,
                        'old_company_id' => $oldPeriod->company_id,
                        'old_name' => $oldPeriod->name,
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
            ->replace('Á', 'A')
            ->replace('É', 'E')
            ->replace('Í', 'I')
            ->replace('Ó', 'O')
            ->replace('Ú', 'U')
            ->toString();
    }
}
