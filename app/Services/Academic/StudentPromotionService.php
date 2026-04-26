<?php

namespace App\Services\Academic;

use App\Models\Academic\EducationalLevel;

class StudentPromotionService
{
    public function getNextLevelAndNumber(
        EducationalLevel $currentLevel,
        int $currentNumber
    ): array {
        if ($currentNumber < $currentLevel->end_number) {
            return [
                'status' => 'promoted',
                'current_level_id' => $currentLevel->id,
                'current_number' => $currentNumber,
                'next_level_id' => $currentLevel->id,
                'next_number' => $currentNumber + 1,
                'is_graduated' => false,
            ];
        }

        $nextLevel = $currentLevel->nextEducationalLevel;

        if (! $nextLevel) {
            return [
                'status' => 'graduated',
                'current_level_id' => $currentLevel->id,
                'current_number' => $currentNumber,
                'next_level_id' => null,
                'next_number' => null,
                'is_graduated' => true,
            ];
        }

        return [
            'status' => 'promoted',
            'current_level_id' => $currentLevel->id,
            'current_number' => $currentNumber,
            'next_level_id' => $nextLevel->id,
            'next_number' => $nextLevel->start_number,
            'is_graduated' => false,
        ];
    }
}

// Como se sugiere usar este service:-------------------------  OJO ---------------
//1. Usuario abre matrícula nueva
//2. Selecciona estudiante
//3. Backend busca su último nivel y grado
//4. Backend calcula sugerencia
//5. Frontend muestra:
//   "Sugerido: Bachillerato 1"
//6. Usuario confirma o cambia manualmente
//7. Se guarda la matrícula
