<?php

namespace App\Console\Commands;

use App\Models\Administration\Position;
use App\Models\Administration\TenantPosition;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1TenantPositions extends Command
{
    protected $signature = 'migrate:v1-tenant-positions {--fresh : Delete migrated tenant positions before importing again}';

    protected $description = 'Migrate tenant positions from v1 gn.company_persons';

    public function handle(): int
    {
        $this->info('Starting tenant positions migration from v1...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'tenant_position')
                ->pluck('new_id');

            TenantPosition::query()
                ->whereIn('id', $ids)
                ->delete();

            MigrationIdMap::query()
                ->where('entity', 'tenant_position')
                ->delete();
        }

        $rows = DB::connection('pgsql_v1')
            ->table('gn.company_persons')
            ->orderBy('created_at')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'tenant_position')
                        ->where('old_id', $row->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $tenantId = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $row->company_id)
                    ->value('new_id');

                $personId = MigrationIdMap::query()
                    ->where('entity', 'person')
                    ->where('old_id', $row->person_id)
                    ->value('new_id');

                if (! $tenantId) {
                    $this->warn("Tenant not found for old company_id {$row->company_id}");
                    $failed++;
                    continue;
                }

                if (! $personId) {
                    $this->warn("Person not found for old person_id {$row->person_id}");
                    $failed++;
                    continue;
                }

                $positionCode = $this->mapPositionCode(
                    $row->relationship_type
                );

                if (! $positionCode) {
                    $this->warn(
                        "Unknown position: {$row->relationship_type}"
                    );

                    $failed++;
                    continue;
                }

                $positionId = Position::query()
                    ->where('code', $positionCode)
                    ->value('id');

                if (! $positionId) {
                    $this->warn(
                        "Position not found: {$positionCode}"
                    );

                    $failed++;
                    continue;
                }

                $existing = TenantPosition::query()
                    ->where('tenant_id', $tenantId)
                    ->where('person_id', $personId)
                    ->where('position_id', $positionId)
                    ->first();

                if ($existing) {
                    MigrationIdMap::query()->create([
                        'entity' => 'tenant_position',
                        'old_id' => $row->id,
                        'new_id' => $existing->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'relationship_type' => $row->relationship_type,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                $tenantPosition = TenantPosition::query()->create([
                    'tenant_id' => $tenantId,
                    'person_id' => $personId,
                    'position_id' => $positionId,

                    'signature' => $this->normalizeSignaturePath($row->firm),

                    'order_to_sign' => 1,

                    'is_active' => (bool) ($row->is_active ?? true),

                    'start_date' => null,
                    'end_date' => null,

                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'tenant_position',
                    'old_id' => $row->id,
                    'new_id' => $tenantPosition->id,
                    'metadata' => [
                        'old_company_id' => $row->company_id,
                        'old_person_id' => $row->person_id,
                        'relationship_type' => $row->relationship_type,
                        'title' => $row->title,
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

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function mapPositionCode(?string $relationshipType): ?string
    {
        $value = str($relationshipType ?? '')
            ->ascii()
            ->upper()
            ->trim()
            ->toString();

        return match ($value) {

            'RECTOR(A)',
            'RECTOR/A' => 'RECTOR',

            'SECRETARIO(A)',
            'SECRETARIO/A' => 'SECRETARY',

            'INSPECTOR(A)',
            'INSPECTOR/A' => 'INSPECTOR',

            'DIRECTOR(A)',
            'DIRECTOR/A' => 'DIRECTOR',

            default => null,
        };
    }

    private function normalizeSignaturePath(?string $signature): ?string
    {
        $signature = trim((string) $signature);

        if ($signature === '') {
            return null;
        }

        return str($signature)
            ->replaceStart(
                'firm_signatures/',
                'tenant_positions/signature/'
            )
            ->toString();
    }
}
