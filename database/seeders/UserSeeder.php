<?php

namespace Database\Seeders;

use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuarios GLOBALes (sin tenant_id)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.test'],
            ['name' => 'Global Admin', 'password' => bcrypt('123456')]
        );

        // Asignación por tenant:
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            // Fija tenant actual + team_id
            $tenant->makeCurrent();
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

            // Asigna roles *en este tenant*
            $admin->assignRole('admin');   // rol "admin" de ESTE tenant
        }

        // Limpieza
        Tenant::forgetCurrent();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
