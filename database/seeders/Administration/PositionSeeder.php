<?php


namespace Database\Seeders\Administration;

use App\Models\Administration\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Rector(a)', 'code' => 'RECTOR', 'is_active' => true],
            ['name' => 'Vicerrector(a)', 'code' => 'VICERRECTOR', 'is_active' => true],
            ['name' => 'Decano(a)', 'code' => 'DEAN', 'is_active' => true],
            ['name' => 'Secretario(a)', 'code' => 'SECRETARY', 'is_active' => true],
            ['name' => 'Inspector(a)', 'code' => 'INSPECTOR', 'is_active' => true],
            ['name' => 'Coordinador(a)', 'code' => 'COORDINATOR', 'is_active' => true],
        ];

        foreach ($positions as $position) {
            Position::query()->updateOrCreate(
                ['code' => $position['code']],
                $position
            );
        }
    }
}
