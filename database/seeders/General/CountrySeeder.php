<?php

namespace Database\Seeders\General;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['code' => 'EC', 'name' => 'Ecuador'],
            ['code' => 'CO', 'name' => 'Colombia'],
            ['code' => 'PE', 'name' => 'Perú'],
            ['code' => 'MX', 'name' => 'México'],
            ['code' => 'AR', 'name' => 'Argentina'],
            ['code' => 'CL', 'name' => 'Chile'],
            ['code' => 'CU', 'name' => 'Cuba'],
            ['code' => 'VE', 'name' => 'Venezuela'],
            ['code' => 'BO', 'name' => 'Bolivia'],
            ['code' => 'PA', 'name' => 'Panamá'],
        ];

        DB::table('countries')->upsert(
            $countries,
            ['code'],
            ['name']
        );
    }
}
