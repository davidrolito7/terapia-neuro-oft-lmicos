<?php

namespace App\Filament\Resources\Citas\Tables;

use App\Models\Cita;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CitasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paciente_nombre')
                    ->label('Paciente')
                    ->getStateUsing(fn ($record) => $record->paciente?->nombre_completo ?? '—')
                    ->searchable(
                        query: fn ($query, $search) => $query->whereHas(
                            'paciente',
                            fn ($q) => $q
                                ->where('nombre', 'like', "%{$search}%")
                                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                                ->orWhere('apellido_materno', 'like', "%{$search}%")
                        )
                    )
                    ->weight('medium'),

                TextColumn::make('inicio')
                    ->label('Fecha y hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('duracion_minutos')
                    ->label('Duración')
                    ->formatStateUsing(fn ($state) => "{$state} min"),

                TextColumn::make('color_nombre')
                    ->label('Color')
                    ->getStateUsing(fn ($record) => self::colorNombre($record->color))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'atendida'  => 'success',
                        'cancelada' => 'danger',
                        default     => 'warning',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pendiente'  => 'Pendiente',
                        'atendida'   => 'Atendida',
                        'cancelada'  => 'Cancelada',
                        default      => $state,
                    }),

                TextColumn::make('tipo_recurrencia')
                    ->label('Repetición')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'diaria'  => 'Diaria',
                        'semanal' => 'Semanal',
                        'mensual' => 'Mensual',
                        default   => '—',
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('inicio', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'atendida'   => 'Atendida',
                        'cancelada'  => 'Cancelada',
                    ]),

                Filter::make('periodo')
                    ->schema([
                        Select::make('periodo')
                            ->label('Período rápido')
                            ->options([
                                'hoy'    => 'Hoy',
                                'semana' => 'Esta semana',
                                'mes'    => 'Este mes',
                            ])
                            ->placeholder('Todos'),
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['periodo'] ?? null) {
                            'hoy'    => $query->whereDate('inicio', today()),
                            'semana' => $query->whereBetween('inicio', [now()->startOfWeek(), now()->endOfWeek()]),
                            'mes'    => $query->whereMonth('inicio', now()->month)->whereYear('inicio', now()->year),
                            default  => $query,
                        };
                    })
                    ->indicateUsing(fn (array $data) => match ($data['periodo'] ?? null) {
                        'hoy'    => 'Período: Hoy',
                        'semana' => 'Período: Esta semana',
                        'mes'    => 'Período: Este mes',
                        default  => null,
                    }),

                Filter::make('rango_fecha')
                    ->schema([
                        DatePicker::make('desde')
                            ->label('Desde')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('hasta')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn ($q, $v) => $q->whereDate('inicio', '>=', $v))
                            ->when($data['hasta'] ?? null, fn ($q, $v) => $q->whereDate('inicio', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['desde'])) {
                            $indicators[] = 'Desde: ' . Carbon::parse($data['desde'])->format('d/m/Y');
                        }
                        if (!empty($data['hasta'])) {
                            $indicators[] = 'Hasta: ' . Carbon::parse($data['hasta'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->recordActions([
                Action::make('atender')
                    ->label('Atender')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->schema([
                        Textarea::make('observaciones')
                            ->label('Observaciones de la sesión')
                            ->required()
                            ->rows(4)
                            ->placeholder('Describe lo realizado en la cita...'),
                    ])
                    ->action(fn (Cita $record, array $data) => $record->update([
                        'estado'        => 'atendida',
                        'observaciones' => $data['observaciones'],
                    ]))
                    ->visible(fn (Cita $record) => $record->estado === 'pendiente'),

                Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Cancelar esta cita?')
                    ->modalDescription('La cita quedará marcada como cancelada.')
                    ->action(fn (Cita $record) => $record->update(['estado' => 'cancelada']))
                    ->visible(fn (Cita $record) => $record->estado === 'pendiente'),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function colorNombre(string $hex): string
    {
        return match ($hex) {
            '#b8dff0' => '🔵 Azul cielo',
            '#c8ebc1' => '🟢 Verde menta',
            '#f9c4d2' => '🌸 Rosa palo',
            '#d4b8e0' => '🟣 Lavanda',
            '#ffe9a8' => '🌟 Amarillo sol',
            '#ffd4b8' => '🍑 Durazno',
            '#b8e0e0' => '💎 Verde agua',
            '#f0c8b8' => '🐚 Salmón',
            default   => $hex,
        };
    }
}
