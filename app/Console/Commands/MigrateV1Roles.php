<?php

namespace App\Console\Commands;

use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class MigrateV1Roles extends Command
{
    protected $signature = 'migrate:v1-roles {--fresh : Delete migrated role mappings and migrated roles except admin}';

    protected $description = 'Migrate v1 roles to v2 roles for every tenant, preserving seeded admin role';

    public function handle(): int
    {
        $this->info('Starting roles migration from v1...');

        if ($this->option('fresh')) {
            $roleIds = MigrationIdMap::query()
                ->where('entity', 'role')
                ->pluck('new_id')
                ->unique();

            Role::query()
                ->whereIn('id', $roleIds)
                ->where('name', '<>', 'admin')
                ->delete();

            MigrationIdMap::query()
                ->where('entity', 'role')
                ->delete();
        }

        $oldRoles = DB::connection('pgsql_v1')
            ->table('roles')
            ->orderBy('name')
            ->get();

        $tenants = Tenant::query()->get();

        $created = 0;
        $mapped = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

            foreach ($oldRoles as $oldRole) {
                $oldMapId = $oldRole->id . '|' . $tenant->id;

                if (
                    MigrationIdMap::query()
                        ->where('entity', 'role')
                        ->where('old_id', $oldMapId)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $roleName = $this->mapRoleName($oldRole->name);

                $role = Role::query()->firstOrCreate(
                    [
                        'name' => $roleName,
                        'guard_name' => $oldRole->guard_name ?? 'api',
                        'tenant_id' => $tenant->id,
                    ],
                    []
                );

                MigrationIdMap::query()->create([
                    'entity' => 'role',
                    'old_id' => $oldMapId,
                    'new_id' => $role->id,
                    'metadata' => [
                        'old_role_id' => $oldRole->id,
                        'old_role_name' => $oldRole->name,
                        'new_role_name' => $roleName,
                        'tenant_id' => $tenant->id,
                        'mapped_to_admin' => $roleName === 'admin',
                    ],
                ]);

                if ($roleName === 'admin') {
                    $mapped++;
                } else {
                    $created++;
                }
            }
        }

        Tenant::forgetCurrent();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->table(
            ['Created', 'Mapped to admin', 'Skipped', 'Failed'],
            [[$created, $mapped, $skipped, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function mapRoleName(string $name): string
    {
        $normalized = str($name)
            ->ascii()
            ->lower()
            ->trim()
            ->toString();

        return match ($normalized) {
            'administrator',
            'administrador' => 'admin',

            default => trim($name),
        };
    }
}
