<?php

namespace App\Filament\Resources\ExerciseSessions\Pages;

use App\Filament\Resources\ExerciseSessions\ExerciseSessionResource;
use App\Models\SesionEjercicio;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExerciseSession extends EditRecord
{
    protected static string $resource = ExerciseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
