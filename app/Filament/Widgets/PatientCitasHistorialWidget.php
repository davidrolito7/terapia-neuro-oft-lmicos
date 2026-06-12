<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class PatientCitasHistorialWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public ?Model $record = null;

    protected string $view = 'filament.widgets.patient-citas-historial';

    protected function getViewData(): array
    {
        if (!$this->record) {
            return [
                'citas'            => collect(),
                'totalAtendidas'   => 0,
                'totalCanceladas'  => 0,
            ];
        }

        $citas = $this->record
            ->citas()
            ->whereIn('estado', ['atendida', 'cancelada'])
            ->orderByDesc('inicio')
            ->get();

        return [
            'citas'           => $citas,
            'totalAtendidas'  => $citas->where('estado', 'atendida')->count(),
            'totalCanceladas' => $citas->where('estado', 'cancelada')->count(),
        ];
    }
}
