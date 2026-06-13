<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Citas\CitaResource;
use App\Models\Cita;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;

class CalendarioCitas extends Page
{
    protected string $view = 'filament.pages.calendario-citas';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Calendario';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Agenda';
    }

    protected static ?string $title = 'Calendario de Citas';

    public ?array $citaSeleccionada = null;

    public bool $mostrarDetalle = false;

    public string $estadoInput = '';

    public string $observacionesInput = '';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nueva_cita')
                ->label('Nueva Cita')
                ->icon('heroicon-o-plus')
                ->url(CitaResource::getUrl('create')),
        ];
    }

    public function getCalendarEvents(): array
    {
        return Cita::with('paciente')
            ->get()
            ->map(fn (Cita $cita) => [
                'id'              => $cita->id,
                'title'           => $cita->paciente?->nombre_completo ?? 'Sin paciente',
                'start'           => $cita->inicio->toIso8601String(),
                'end'             => $cita->fin->toIso8601String(),
                'backgroundColor' => $cita->color,
                'borderColor'     => $cita->color,
                'textColor'       => '#1e293b',
                'extendedProps'   => [
                    'estado'        => $cita->estado,
                    'duracion'      => $cita->duracion_minutos,
                    'observaciones' => $cita->observaciones,
                    'recurrente'    => $cita->tipo_recurrencia !== 'ninguna' || $cita->cita_padre_id,
                ],
            ])
            ->toArray();
    }

    public function getCitasHoy(): int
    {
        return Cita::whereDate('inicio', today())->where('estado', 'pendiente')->count();
    }

    public function getCitasSemana(): int
    {
        return Cita::whereBetween('inicio', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('estado', 'pendiente')
            ->count();
    }

    public function getCitasMes(): int
    {
        return Cita::whereMonth('inicio', now()->month)
            ->whereYear('inicio', now()->year)
            ->where('estado', 'pendiente')
            ->count();
    }

    public function seleccionarCita(int $id): void
    {
        $cita = Cita::with('paciente')->find($id);
        if (!$cita) {
            return;
        }

        $citaAnterior = Cita::where('paciente_id', $cita->paciente_id)
            ->where('id', '!=', $cita->id)
            ->where('estado', 'atendida')
            ->whereNotNull('observaciones')
            ->where('inicio', '<', $cita->inicio)
            ->orderByDesc('inicio')
            ->first();

        $this->citaSeleccionada = [
            'id'                  => $cita->id,
            'paciente'            => $cita->paciente?->nombre_completo ?? '—',
            'inicio'              => $cita->inicio->format('d/m/Y'),
            'hora'                => $cita->inicio->format('H:i'),
            'duracion'            => $cita->duracion_minutos,
            'color'               => $cita->color,
            'recurrente'          => $cita->tipo_recurrencia !== 'ninguna' || $cita->cita_padre_id,
            'edit_url'            => CitaResource::getUrl('edit', ['record' => $cita->id]),
            'cita_anterior_fecha' => $citaAnterior?->inicio->format('d/m/Y'),
            'cita_anterior_obs'   => $citaAnterior?->observaciones,
        ];
        $this->estadoInput        = $cita->estado;
        $this->observacionesInput = $cita->observaciones ?? '';
        $this->mostrarDetalle     = true;
    }

    public function cerrarDetalle(): void
    {
        $this->mostrarDetalle     = false;
        $this->citaSeleccionada   = null;
        $this->estadoInput        = '';
        $this->observacionesInput = '';
    }

    public function guardarCita(): void
    {
        if (!$this->citaSeleccionada) {
            return;
        }

        Cita::find($this->citaSeleccionada['id'])?->update([
            'estado'        => $this->estadoInput,
            'observaciones' => $this->observacionesInput ?: null,
        ]);

        $this->cerrarDetalle();
        $this->dispatch('actualizar-calendario', eventos: $this->getCalendarEvents());
    }

    public function getProximasCitas(): array
    {
        return Cita::with('paciente')
            ->where('inicio', '>=', now()->startOfDay())
            ->where('estado', '!=', 'cancelada')
            ->orderBy('inicio')
            ->limit(12)
            ->get()
            ->map(fn (Cita $cita) => [
                'id'       => $cita->id,
                'paciente' => $cita->paciente?->nombre_completo ?? '—',
                'hora'     => $cita->inicio->format('H:i'),
                'fecha'    => $cita->inicio->format('d/m'),
                'color'    => $cita->color,
                'estado'   => $cita->estado,
                'hoy'      => $cita->inicio->isToday(),
                'manana'   => $cita->inicio->isTomorrow(),
                'edit_url' => CitaResource::getUrl('edit', ['record' => $cita->id]),
            ])
            ->toArray();
    }
}
