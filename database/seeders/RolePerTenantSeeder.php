<?php

namespace Database\Seeders;

use App\Models\Administration\Permission;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePerTenantSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'api';

        // Roles que quieres crear por cada tenant
        $rolesPerTenant = [
            'admin'  => [
                'List permissions',
                'Search permissions',
                'Create permissions',
                'Update permissions',
                'Delete permissions',
                'List roles',
                'Search roles',
                'Create roles',
                'Update roles',
                'Delete roles',
                'List users',
                'Search users',
                'Create users',
                'Update users',
                'Delete users',
                'List tenants',
                'Search tenants',
                'Create tenants',
                'Update tenants',
                'Delete tenants'],
        ];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Marca tenant actual (Spatie Multitenancy)
            $tenant->makeCurrent();

            // Fija el team_id para spatie/permission (¡crítico!)
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

            foreach ($rolesPerTenant as $roleName => $perms) {
                // Rol por tenant
                $role = Role::firstOrCreate(
                    ['name' => $roleName, 'guard_name' => $guard, 'tenant_id' => $tenant->id],
                    []
                );

                // Permisos globales a asignar al rol de este tenant
                $permissions = Permission::whereIn('name', $perms)
                    ->where('guard_name', $guard)
                    ->get();

                $role->syncPermissions($permissions);
            }
        }

        // Limpia "current" para evitar efectos colaterales
        Tenant::forgetCurrent();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
