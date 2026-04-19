<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos personales')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('nombre')
                                ->label('Nombre(s)')
                                ->required()
                                ->maxLength(100)
                                ->columnSpan(1),

                            TextInput::make('apellido_paterno')
                                ->label('Apellido paterno')
                                ->required()
                                ->maxLength(100)
                                ->columnSpan(1),

                            TextInput::make('apellido_materno')
                                ->label('Apellido materno')
                                ->maxLength(100)
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make('Contacto')
                    ->icon('heroicon-o-phone')
                    ->columns(2)
                    ->schema([
                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->rules([
                                fn ($record) => Rule::unique('pacientes', 'telefono')
                                    ->ignore($record?->id),
                            ])
                            ->validationMessages([
                                'unique' => 'Este número de teléfono ya está registrado.',
                            ]),

                        DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de nacimiento')
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),

                        TextInput::make('direccion')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Notas')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notas')
                            ->label('')
                            ->rows(4)
                            ->placeholder('Historial clínico relevante, condiciones especiales, observaciones del terapeuta...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
