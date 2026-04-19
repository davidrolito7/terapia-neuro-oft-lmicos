<?php

namespace App\Filament\Resources\ExerciseSessions\Pages;

use App\Filament\Resources\ExerciseSessions\ExerciseSessionResource;
use App\Models\SesionEjercicio;
use Filament\Resources\Pages\CreateRecord;

class CreateExerciseSession extends CreateRecord
{
    protected static string $resource = ExerciseSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        if (isset($data['fecha_inicio'], $data['total_sesiones'], $data['veces_por_dia'], $data['frecuencia_dias'])) {
            $data['fecha_fin'] = SesionEjercicio::calcularFechaFin(
                $data['fecha_inicio'],
                (int) $data['total_sesiones'],
                (int) $data['veces_por_dia'],
                (int) $data['frecuencia_dias'],
            )->format('Y-m-d');
        }

        return $data;
    }
}
