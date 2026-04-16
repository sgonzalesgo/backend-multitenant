<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollmentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $statuses = [
            [
                'id' => (string) Str::uuid(),
                'code' => 'pending',
                'name' => 'Pendiente',
                'description' => 'Matrícula creada pero aún no confirmada',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'active',
                'name' => 'Activa',
                'description' => 'El estudiante está actualmente matriculado',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'suspended',
                'name' => 'Suspendida',
                'description' => 'La matrícula está temporalmente suspendida',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'withdrawn',
                'name' => 'Retirada',
                'description' => 'El estudiante se retiró voluntariamente',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'completed',
                'name' => 'Completada',
                'description' => 'El estudiante completó el programa',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'cancelled',
                'name' => 'Cancelada',
                'description' => 'La matrícula fue cancelada',
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('enrollment_statuses')->insert($statuses);
    }
}
