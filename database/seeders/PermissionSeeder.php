<?php

namespace Database\Seeders;

use App\Models\Administration\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'api'; // usa el mismo guard que en tu auth

        $perms = [
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
            'Delete tenants',
        ];

        foreach ($perms as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                []
            );
        }
    }
}
