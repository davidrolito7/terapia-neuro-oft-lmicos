<?php

namespace App\Filament\Resources\Citas\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CitaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos de la cita')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Select::make('paciente_id')
                            ->label('Paciente')
                            ->relationship('paciente', 'nombre')
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => $record->nombre_completo
                            )
                            ->searchable(['nombre', 'apellido_paterno', 'apellido_materno'])
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            DateTimePicker::make('inicio')
                                ->label('Fecha y hora de inicio')
                                ->required()
                                ->minutesStep(15)
                                ->native(false)
                                ->displayFormat('d/m/Y H:i')
                                ->default(now()->setMinutes(0)->addHour())
                                ->columnSpan(1),

                            Select::make('duracion_minutos')
                                ->label('Duración')
                                ->options([
                                    30  => '30 minutos',
                                    45  => '45 minutos',
                                    60  => '1 hora',
                                    90  => '1 hora 30 min',
                                    120 => '2 horas',
                                ])
                                ->default(60)
                                ->required()
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make('Repetición')
                    ->icon('heroicon-o-arrow-path')
                    ->collapsed()
                    ->description('¿Con qué frecuencia se repite esta cita?')
                    ->schema([
                        // Campo visual (no se guarda en DB directamente)
                        Select::make('recurrencia_preset')
                            ->label('Repetir')
                            ->dehydrated(false)
                            ->options([
                                'ninguna'       => 'Nunca',
                                'diaria'        => 'Todos los días',
                                'semanal'       => 'Cada semana',
                                'mensual'       => 'Cada mes',
                                'personalizada' => 'Personalizada…',
                            ])
                            ->default('ninguna')
                            ->live()
                            ->afterStateHydrated(function ($component, $record, $set) {
                                if (! $record) {
                                    $component->state('ninguna');
                                    return;
                                }
                                if ($record->tipo_recurrencia === 'ninguna') {
                                    $component->state('ninguna');
                                } elseif ($record->intervalo_recurrencia > 1) {
                                    $component->state('personalizada');
                                    $set('frecuencia_personalizada', $record->tipo_recurrencia);
                                } else {
                                    $component->state($record->tipo_recurrencia);
                                }
                            })
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state === 'personalizada') {
                                    $set('tipo_recurrencia', 'semanal');
                                    $set('frecuencia_personalizada', 'semanal');
                                    $set('intervalo_recurrencia', 2);
                                } else {
                                    $set('tipo_recurrencia', $state);
                                    $set('intervalo_recurrencia', 1);
                                    if ($state !== 'semanal') {
                                        $set('dias_semana', []);
                                    }
                                }
                            }),

                        // Campo real guardado en DB (oculto visualmente)
                        Hidden::make('tipo_recurrencia')->default('ninguna'),

                        // Sub-sección de personalización (solo visible cuando 'personalizada')
                        Grid::make(2)
                            ->visible(fn ($get) => $get('recurrencia_preset') === 'personalizada')
                            ->schema([
                                Select::make('frecuencia_personalizada')
                                    ->label('Frecuencia')
                                    ->dehydrated(false)
                                    ->options([
                                        'diaria'  => 'Días',
                                        'semanal' => 'Semanas',
                                        'mensual' => 'Meses',
                                    ])
                                    ->default('semanal')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('tipo_recurrencia', $state ?? 'semanal');
                                        if ($state !== 'semanal') {
                                            $set('dias_semana', []);
                                        }
                                    })
                                    ->afterStateHydrated(function ($component, $record) {
                                        if ($record && $record->tipo_recurrencia !== 'ninguna') {
                                            $component->state($record->tipo_recurrencia);
                                        }
                                    }),

                                TextInput::make('intervalo_recurrencia')
                                    ->label('Cada')
                                    ->numeric()
                                    ->default(2)
                                    ->minValue(1)
                                    ->maxValue(52)
                                    ->suffix(fn ($get) => match ($get('tipo_recurrencia')) {
                                        'diaria'  => 'días',
                                        'semanal' => 'semanas',
                                        'mensual' => 'meses',
                                        default   => '',
                                    }),
                            ]),

                        // Días de la semana (semanal preset O personalizada+semanas)
                        CheckboxList::make('dias_semana')
                            ->label('Días de la semana')
                            ->options([
                                1 => 'Lun',
                                2 => 'Mar',
                                3 => 'Mié',
                                4 => 'Jue',
                                5 => 'Vie',
                                6 => 'Sáb',
                                7 => 'Dom',
                            ])
                            ->columns(7)
                            ->gridDirection('row')
                            ->visible(fn ($get) =>
                                $get('recurrencia_preset') === 'semanal' ||
                                ($get('recurrencia_preset') === 'personalizada' && $get('tipo_recurrencia') === 'semanal')
                            ),

                        DatePicker::make('fin_recurrencia')
                            ->label('Termina el')
                            ->displayFormat('d/m/Y')
                            ->minDate(now())
                            ->helperText('Si no se indica, se generan 6 meses de citas.')
                            ->visible(fn ($get) => $get('recurrencia_preset') !== 'ninguna'),
                    ]),

                Section::make('Estado y observaciones')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->collapsed()
                    ->schema([
                        Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'atendida'  => 'Atendida',
                                'cancelada' => 'Cancelada',
                            ])
                            ->default('pendiente')
                            ->required(),

                        Textarea::make('observaciones')
                            ->label('Observaciones de la sesión')
                            ->rows(4)
                            ->placeholder('Describe lo realizado en la cita...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
