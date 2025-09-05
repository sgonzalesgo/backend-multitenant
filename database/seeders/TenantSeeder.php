<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Administration\Tenant;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::create([
            'name'   => 'Cristo Rey',
            'domain' => 'cr', // o subdomain, slug, etc.
        ]);

        Tenant::create([
            'name'   => 'Nuevo Ecuador',
            'domain' => 'ne',
        ]);
    }
}

