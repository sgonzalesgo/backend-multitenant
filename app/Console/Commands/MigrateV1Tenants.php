<?php

namespace App\Console\Commands;

use App\Models\MigrationIdMap;
use App\Models\Administration\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateV1Tenants extends Command
{
    protected $signature = 'migrate:v1-tenants {--fresh : Delete migrated tenants before importing again}';

    protected $description = 'Migrate tenants from v1 gn.companies to v2 tenants';

    public function handle(): int
    {
        $this->info('Starting tenants migration from v1...');

        if (! Schema::hasTable('migration_id_maps')) {
            $this->error('The migration_id_maps table does not exist. Run php artisan migrate first.');

            return self::FAILURE;
        }

        $companies = DB::connection('pgsql_v1')
            ->table('gn.companies')
            ->orderBy('name')
            ->get();

        $this->info("Found {$companies->count()} companies in v1.");

        if ($companies->isEmpty()) {
            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->warn('Fresh option enabled. Deleting previous tenant mappings and migrated tenants...');

            $tenantIds = MigrationIdMap::query()
                ->where('entity', 'tenant')
                ->pluck('new_id');

            Tenant::query()
                ->whereIn('id', $tenantIds)
                ->delete();

            MigrationIdMap::query()
                ->where('entity', 'tenant')
                ->delete();
        }

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($companies as $company) {
                $existingMap = MigrationIdMap::query()
                    ->where('entity', 'tenant')
                    ->where('old_id', $company->id)
                    ->first();

                if ($existingMap) {
                    $this->line("Skipped: {$company->name} already migrated.");
                    $skipped++;
                    continue;
                }

                $domain = $this->resolveDomain($company);

                if (! $domain) {
                    $this->error("Company {$company->id} has no valid slug/domain.");
                    $failed++;
                    continue;
                }

                if (Tenant::query()->where('domain', $domain)->exists()) {
                    $domain = $domain . '-' . substr((string) $company->id, 0, 8);
                }

                $tenant = Tenant::query()->create([
                    'name' => $company->name,
                    'domain' => $domain,
                    'logo' => $company->logo ?? null,
                    'address' => $company->address ?? null,
                    'phone' => $company->phone ?? null,
                    'email' => $company->email ?? null,
                    'legal_id' => $company->legal_id ?? null,
                    'legal_id_type' => $company->legal_id_type ?? null,
                    'is_active' => (bool) ($company->is_active ?? true),
                    'business_name' => $company->business_name ?? null,
                    'campus_logo' => $company->campus_logo ?? null,
                    'campus_type' => $company->campus_type ?? null,
                    'slogan' => $company->slogan ?? null,
                    'amie_code' => $company->amie_code ?? null,
                    'city' => $company->city ?? null,
                    'state' => $company->state ?? null,
                    'country' => $company->country ?? null,
                    'country_logo' => $company->country_logo ?? null,
                    'country_logo_position_right' => (bool) ($company->country_logo_position_right ?? false),
                    'zip' => $company->zip ?? null,
                ]);

                MigrationIdMap::query()->create([
                    'entity' => 'tenant',
                    'old_id' => $company->id,
                    'new_id' => $tenant->id,
                    'metadata' => [
                        'old_name' => $company->name,
                        'old_slug' => $company->slug ?? null,
                        'new_domain' => $domain,
                    ],
                ]);

                $this->info("Migrated: {$company->name} -> {$tenant->id}");

                $created++;
            }

            DB::commit();

            $this->newLine();
            $this->info('Tenants migration completed.');
            $this->table(
                ['Created', 'Skipped', 'Failed'],
                [[$created, $skipped, $failed]]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->error('Tenants migration failed.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveDomain(object $company): ?string
    {
        $slug = trim((string) ($company->slug ?? ''));

        if ($slug !== '') {
            return $slug;
        }

        $name = trim((string) ($company->name ?? ''));

        if ($name === '') {
            return null;
        }

        return str($name)
            ->lower()
            ->slug('-')
            ->toString();
    }
}
