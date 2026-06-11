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
                'code' => 'pending',
                'name' => 'Pendiente',
                'description' => 'Matrícula creada pero aún no confirmada',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'active',
                'name' => 'Activa',
                'description' => 'El estudiante está actualmente matriculado',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'suspended',
                'name' => 'Suspendida',
                'description' => 'La matrícula está temporalmente suspendida',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'withdrawn',
                'name' => 'Retirada',
                'description' => 'El estudiante se retiró voluntariamente',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'completed',
                'name' => 'Completada',
                'description' => 'El estudiante completó el programa',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelada',
                'description' => 'La matrícula fue cancelada',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'code' => 'dropout',
                'name' => 'Desertor',
                'description' => 'El estudiante abandonó el proceso académico sin retiro formal',
                'is_active' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($statuses as $status) {
            $existing = DB::table('enrollment_statuses')
                ->where('code', $status['code'])
                ->first();

            if ($existing) {
                DB::table('enrollment_statuses')
                    ->where('code', $status['code'])
                    ->update([
                        'name' => $status['name'],
                        'description' => $status['description'],
                        'is_active' => $status['is_active'],
                        'sort_order' => $status['sort_order'],
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('enrollment_statuses')->insert([
                'id' => (string) Str::uuid(),
                'code' => $status['code'],
                'name' => $status['name'],
                'description' => $status['description'],
                'is_active' => $status['is_active'],
                'sort_order' => $status['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
