<?php

namespace App\Filament\Resources\ExerciseSessions\Tables;

use App\Models\LogSesion;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ExerciseSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paciente.nombre_completo')
                    ->label('Paciente')
                    ->getStateUsing(fn($record) => $record->paciente->nombre_completo)
                    ->searchable(['pacientes.nombre', 'pacientes.apellido_paterno'])
                    ->sortable(),

                TextColumn::make('planEjercicio.nombre')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),

                // % calculado desde logs_sesion / total_sesiones
                TextColumn::make('porcentaje_terapia')
                    ->label('% Terapia')
                    ->getStateUsing(function ($record) {
                        $total = (int) $record->total_sesiones;
                        if ($total <= 0) return 0;

                        $completadas = LogSesion::where('paciente_id', $record->paciente_id)
                            ->where('plan_ejercicio_id', $record->plan_ejercicio_id)
                            ->count();

                        return min(100, (int) round($completadas / $total * 100));
                    })
                    ->formatStateUsing(fn($state) => "{$state}%")
                    ->badge()
                    ->color(fn($state) => match (true) {
                        (int) $state >= 100 => 'success',
                        (int) $state >= 50  => 'warning',
                        default             => 'danger',
                    })
                    ->sortable(false),

                TextColumn::make('total_sesiones')
                    ->label('Total sesiones')
                    ->numeric()
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                // Sesiones completadas hoy
                TextColumn::make('sesiones_hoy')
                    ->label('Sesiones hoy')
                    ->getStateUsing(function ($record) {
                        return \App\Models\LogSesion::where('paciente_id', $record->paciente_id)
                            ->where('plan_ejercicio_id', $record->plan_ejercicio_id)
                            ->whereDate('created_at', today())
                            ->count()
                            . ' / ' . $record->veces_por_dia;
                    })
                    ->badge()
                    ->color('info'),

                TextColumn::make('fecha_inicio')
                    ->label('Inicio terapia')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('fecha_fin')
                    ->label('Fin terapia')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('completada')->label('Completada'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
