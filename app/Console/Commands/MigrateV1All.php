<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateV1All extends Command
{
    protected $signature = 'migrate:v1-all';

    protected $description = 'Run all v1 to v2 migration commands in the correct order';

    public function handle(): int
    {
        $steps = [
            ['db:seed', ['--class' => 'PermissionSeeder']],
            ['db:seed', ['--class' => 'Database\\Seeders\\Administration\\PositionSeeder']],
            ['db:seed', ['--class' => 'Database\\Seeders\\General\\CountrySeeder']],
            ['db:seed', ['--class' => 'Database\\Seeders\\General\\StateSeeder']],
            ['db:seed', ['--class' => 'Database\\Seeders\\General\\CitySeeder']],
            ['db:seed', ['--class' => 'EnrollmentStatusSeeder']],

            ['migrate:v1-tenants'],
            ['db:seed', ['--class' => 'Database\\Seeders\\Calendar\\CalendarEventTypeSeeder']],

            ['db:seed', ['--class' => 'RolePerTenantSeeder']],
            ['db:seed', ['--class' => 'UserSeeder']],
            ['permission:cache-reset'],

            ['migrate:v1-persons'],
            ['migrate:v1-students'],
            ['migrate:v1-legal-representatives'],
            ['migrate:v1-student-legal-representatives'],
            ['migrate:v1-instructors'],

            ['migrate:v1-academic-years'],
            ['migrate:v1-educational-levels'],
            ['migrate:v1-specialties'],
            ['migrate:v1-educational-level-specialties'],
            ['migrate:v1-modalities'],
            ['migrate:v1-parallels'],
            ['migrate:v1-shifts'],
            ['migrate:v1-courses'],
            ['migrate:v1-subjects'],
            ['migrate:v1-evaluation-periods'],

            ['db:seed', ['--class' => 'EnrollmentStatusSeeder']],
            ['migrate:v1-enrollment-statuses', ['--fresh' => true]],
            ['migrate:v1-enrollments', ['--fresh' => true]],
            ['migrate:v1-users'],

            ['migrate:v1-classrooms'],
            ['migrate:v1-tenant-positions'],
            ['migrate:v1-roles'],
            ['migrate:v1-academic-schedules'],
        ];

        foreach ($steps as $step) {
            $command = $step[0];
            $parameters = $step[1] ?? [];

            $this->newLine();
            $this->info("Running: php artisan {$command}");

            $exitCode = Artisan::call($command, $parameters);

            $output = trim(Artisan::output());

            if ($output !== '') {
                $this->line($output);
            }

            if ($exitCode !== self::SUCCESS) {
                $this->error("Migration stopped at command: {$command}");

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('All v1 migrations completed successfully.');

        return self::SUCCESS;
    }
}
