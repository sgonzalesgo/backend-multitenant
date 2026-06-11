<?php

namespace App\Console\Commands;

use App\Models\Academic\EnrollmentStatus;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1EnrollmentStatuses extends Command
{
    protected $signature = 'migrate:v1-enrollment-statuses {--fresh : Delete previous enrollment status mappings}';

    protected $description = 'Map v1 enrollment statuses to v2 seeded enrollment statuses';

    public function handle(): int
    {
        $this->info('Starting enrollment status mapping from v1...');

        if ($this->option('fresh')) {
            MigrationIdMap::query()
                ->where('entity', 'enrollment_status')
                ->delete();
        }

        $statuses = DB::connection('pgsql_v1')
            ->table('ac.enrollment_status')
            ->orderBy('name')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($statuses as $oldStatus) {
                $existingMap = MigrationIdMap::query()
                    ->where('entity', 'enrollment_status')
                    ->where('old_id', $oldStatus->id)
                    ->first();

                if ($existingMap) {
                    $skipped++;
                    continue;
                }

                $statusCode = $this->mapStatusCode($oldStatus->name);

                if (! $statusCode) {
                    $this->warn("No mapping found for v1 status: {$oldStatus->name}");
                    $failed++;
                    continue;
                }

                $newStatus = EnrollmentStatus::query()
                    ->where('code', $statusCode)
                    ->first();

                if (! $newStatus) {
                    $this->warn("Seeded v2 status not found for code: {$statusCode}");
                    $failed++;
                    continue;
                }

                MigrationIdMap::query()->create([
                    'entity' => 'enrollment_status',
                    'old_id' => $oldStatus->id,
                    'new_id' => $newStatus->id,
                    'metadata' => [
                        'old_name' => $oldStatus->name,
                        'old_company_id' => $oldStatus->company_id ?? null,
                        'mapped_code' => $statusCode,
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

    private function mapStatusCode(string $name): ?string
    {
        $name = str($name)
            ->ascii()
            ->upper()
            ->trim()
            ->toString();

        return match ($name) {
            'MATRICULADO', 'MATRICULADA', 'ACTIVO', 'ACTIVA' => 'active',
            'RETIRADO', 'RETIRADA' => 'withdrawn',
            'DESERTOR', 'DESERTORA' => 'dropout',
            default => null,
        };
    }
}
