<?php

namespace App\Filament\Resources\Patients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_completo')
                    ->label('Paciente')
                    ->getStateUsing(fn ($record) => $record->nombre_completo)
                    ->searchable(['nombre', 'apellido_paterno', 'apellido_materno'])
                    ->sortable(['apellido_paterno']),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),

                TextColumn::make('fecha_nacimiento')
                    ->label('Fecha de nac.')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('sesiones_count')
                    ->label('Sesiones')
                    ->getStateUsing(fn ($record) => $record->sesiones()->count())
                    ->badge()
                    ->color('warning')
                    ->sortable(false),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('apellido_paterno')
            ->filters([])
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
