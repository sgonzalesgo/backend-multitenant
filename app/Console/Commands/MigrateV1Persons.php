<?php

namespace App\Console\Commands;

use App\Models\General\Person;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1Persons extends Command
{
    protected $signature = 'migrate:v1-persons {--fresh : Delete migrated persons before importing again}';

    protected $description = 'Migrate persons from v1 gn.persons to v2 persons';

    public function handle(): int
    {
        $this->info('Starting persons migration from v1...');

        if ($this->option('fresh')) {
            $this->warn('Fresh option enabled. Deleting previous person mappings and migrated persons...');

            $personIds = MigrationIdMap::query()
                ->where('entity', 'person')
                ->pluck('new_id');

            Person::query()
                ->whereIn('id', $personIds)
                ->forceDelete();

            MigrationIdMap::query()
                ->where('entity', 'person')
                ->delete();
        }

        $total = DB::connection('pgsql_v1')->table('gn.persons')->count();

        $this->info("Found {$total} persons in v1.");

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::connection('pgsql_v1')
            ->table('gn.persons')
            ->orderBy('id')
            ->chunk(500, function ($persons) use (&$created, &$skipped, &$failed) {
                DB::beginTransaction();

                try {
                    foreach ($persons as $oldPerson) {
                        $existingMap = MigrationIdMap::query()
                            ->where('entity', 'person')
                            ->where('old_id', $oldPerson->id)
                            ->first();

                        if ($existingMap) {
                            $skipped++;
                            continue;
                        }

                        $person = Person::query()->create([
                            'full_name' => trim($oldPerson->full_name),
                            'photo' => $this->normalizePhotoPath($oldPerson->photo ?? null),
                            'email' => $oldPerson->email ?? null,
                            'phone' => $oldPerson->phone ?? null,
                            'address' => $oldPerson->address ?? null,

                            // v1 tiene textos: country/state/city.
                            // v2 usa ids, por ahora los dejamos null.
                            'country_id' => null,
                            'state_id' => null,
                            'city_id' => null,

                            'zip' => $oldPerson->zip ?? null,
                            'legal_id' => trim($oldPerson->legal_id),
                            'legal_id_type' => $this->normalizeLegalIdType($oldPerson->legal_id_type),
                            'birthday' => $oldPerson->birthday ?? null,
                            'gender' => $this->normalizeGender($oldPerson->gender ?? null),
                            'marital_status' => $oldPerson->marital_status ?? null,
                            'blood_group' => $oldPerson->blood_group ?? null,
                            'nationality' => $oldPerson->nationality ?? null,
                            'deceased_at' => null,
                            'status_changed_at' => null,
                            'created_at' => $oldPerson->created_at ?? now(),
                            'updated_at' => $oldPerson->updated_at ?? now(),
                        ]);

                        MigrationIdMap::query()->create([
                            'entity' => 'person',
                            'old_id' => $oldPerson->id,
                            'new_id' => $person->id,
                            'metadata' => [
                                'old_full_name' => $oldPerson->full_name,
                                'old_legal_id' => $oldPerson->legal_id,
                                'old_legal_id_type' => $oldPerson->legal_id_type,
                            ],
                        ]);

                        $created++;
                    }

                    DB::commit();

                    $this->line("Processed chunk. Created: {$created}, Skipped: {$skipped}, Failed: {$failed}");
                } catch (\Throwable $e) {
                    DB::rollBack();

                    $failed += count($persons);

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

    private function normalizeLegalIdType(?string $value): string
    {
        $value = strtoupper(trim((string) $value));

        return match ($value) {
            'CEDULA', 'CÉDULA', 'CED', 'CI' => 'CEDULA',
            'PASAPORTE', 'PASSPORT' => 'PASAPORTE',
            'RUC' => 'RUC',
            default => $value,
        };
    }

    private function normalizeGender(?string $value): ?string
    {
        $value = trim((string) $value);

        return match (strtolower($value)) {
            'male', 'masculino', 'm' => 'Masculino',
            'female', 'femenino', 'f' => 'Femenino',
            default => $value ?: null,
        };
    }

    private function normalizePhotoPath(?string $photo): ?string
    {
        $photo = trim((string) $photo);

        if ($photo === '') {
            return null;
        }

        return str($photo)
            ->replaceStart('avatars/', 'persons/')
            ->toString();
    }
}
