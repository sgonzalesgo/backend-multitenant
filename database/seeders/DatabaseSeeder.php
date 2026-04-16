<?php

namespace Database\Seeders;

use Database\Seeders\Administration\PositionSeeder;
use Database\Seeders\Calendar\CalendarEventTypeSeeder;
use Database\Seeders\General\CitySeeder;
use Database\Seeders\General\CountrySeeder;
use Database\Seeders\General\StateSeeder;
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
            CalendarEventTypeSeeder::class,
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            PositionSeeder::class,
            EnrollmentStatusSeeder::class
        ]);
    }
}
