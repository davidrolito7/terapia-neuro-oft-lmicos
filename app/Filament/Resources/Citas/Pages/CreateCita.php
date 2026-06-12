<?php

namespace App\Filament\Resources\Citas\Pages;

use App\Filament\Resources\Citas\CitaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCita extends CreateRecord
{
    protected static string $resource = CitaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // tipo_recurrencia viene del campo Hidden; asegurarse de que sea válido
        if (empty($data['tipo_recurrencia']) || ! in_array($data['tipo_recurrencia'], ['ninguna', 'diaria', 'semanal', 'mensual'])) {
            $data['tipo_recurrencia'] = 'ninguna';
        }

        // Para presets simples (no personalizada), el intervalo es siempre 1
        $data['intervalo_recurrencia'] = $data['intervalo_recurrencia'] ?? 1;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->generarRecurrencias();
    }
}
