<?php

namespace Database\Seeders;

use Database\Seeders\Calendar\CalendarEventTypeSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            PermissionSeeder::class,
            RolePerTenantSeeder::class,
            UserSeeder::class,
            CalendarEventTypeSeeder::class
        ]);
    }
}
