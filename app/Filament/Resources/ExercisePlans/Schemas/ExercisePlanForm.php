<?php

namespace App\Filament\Resources\ExercisePlans\Schemas;

use App\Filament\Forms\Components\CanvasPreview;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExercisePlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // ── Datos generales del plan ──────────────────────────────────
                Section::make('Datos del plan')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre del plan')
                            ->placeholder('Ej: Semana 1 — seguimiento básico')
                            ->required()
                            ->maxLength(150),

                        Grid::make(2)->schema([
                            Toggle::make('activo')
                                ->label('Plan activo')
                                ->default(true)
                                ->inline(false),

                            Textarea::make('notas')
                                ->label('Notas generales')
                                ->rows(2)
                                ->placeholder('Observaciones sobre el plan...'),
                        ]),
                    ]),

                // ── Ejercicios del plan ───────────────────────────────────────
                Section::make('Ejercicios')
                    ->icon('heroicon-o-eye')
                    ->description('Agrega los ejercicios que conforman este plan. El paciente los realizará en orden.')
                    ->schema([
                        Repeater::make('ejercicios')
                            ->label('')
                            ->relationship('ejercicios')
                            ->orderColumn('orden')
                            ->reorderable()
                            ->collapsible()
                            ->collapsed(false)
                            ->columns(1)
                            ->itemLabel(fn (array $state): string => self::ejercicioLabel($state))
                            ->schema([

                                // ── Tipo de ejercicio y estímulo ──────────────
                                Grid::make(3)->schema([
                                    Select::make('tipo_ejercicio')
                                        ->label('Tipo de ejercicio')
                                        ->options(self::ejercicioOptions())
                                        ->default('circular')
                                        ->required()
                                        ->live()
                                        ->columnSpan(2),

                                    Select::make('tipo_estimulo')
                                        ->label('Estímulo visual')
                                        ->options([
                                            'dot'   => '● Punto',
                                            'ring'  => '○ Anillo',
                                            'star'  => '★ Estrella',
                                            'cross' => '✚ Cruz',
                                            'emoji' => '😊 Emoji',
                                        ])
                                        ->default('dot')
                                        ->required()
                                        ->live()
                                        ->columnSpan(1),
                                ]),

                                Radio::make('emoji_estimulo')
                                    ->label('Emoji del estímulo')
                                    ->default('⭐')
                                    ->options([
                                        '⭐' => '⭐  Estrella',
                                        '🐝' => '🐝  Abeja',
                                        '💵' => '💵  Billete',
                                        '🍃' => '🍃  Hoja',
                                        '🌈' => '🌈  Arcoíris',
                                        '🐶' => '🐶  Perro',
                                        '🐱' => '🐱  Gato',
                                        '🦆' => '🦆  Pato',
                                    ])
                                    ->inline()
                                    ->inlineLabel(false)
                                    ->visible(fn ($get) => $get('tipo_estimulo') === 'emoji'),

                                // ── Configuración del estímulo ────────────────
                                Grid::make(3)->schema([
                                    TextInput::make('velocidad')
                                        ->label('Velocidad')
                                        ->helperText('1 = lento · 10 = rápido')
                                        ->numeric()->default(5)
                                        ->minValue(1)->maxValue(10)
                                        ->required()->live(),

                                    TextInput::make('tamano')
                                        ->label('Tamaño (px)')
                                        ->numeric()->default(20)
                                        ->minValue(8)->maxValue(60)
                                        ->required()->live(),

                                    ColorPicker::make('color')
                                        ->label('Color')
                                        ->default('#22d3ee')
                                        ->required()->live(),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('duracion')
                                        ->label('Duración (seg)')
                                        ->helperText('0 = sin límite')
                                        ->numeric()->default(60)
                                        ->minValue(0)->required(),

                                    TextInput::make('descanso_segundos')
                                        ->label('Descanso antes del siguiente (seg)')
                                        ->helperText('Pausa entre este ejercicio y el siguiente')
                                        ->numeric()->default(30)
                                        ->minValue(0)->required(),
                                ]),

                                Textarea::make('notas')
                                    ->label('Notas del ejercicio')
                                    ->rows(2)
                                    ->placeholder('Instrucciones específicas para este ejercicio...'),

                                // ── Canvas preview ────────────────────────────
                                CanvasPreview::make('canvas_preview')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('+ Agregar ejercicio')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function ejercicioLabel(array $state): string
    {
        $tipos    = self::ejercicioOptions();
        $tipo     = $tipos[$state['tipo_ejercicio'] ?? ''] ?? ($state['tipo_ejercicio'] ?? 'Nuevo ejercicio');
        $vel      = $state['velocidad'] ?? 5;
        $dur      = isset($state['duracion']) && $state['duracion'] > 0 ? $state['duracion'] . 's' : '∞';
        $descanso = isset($state['descanso_segundos']) ? "  ·  descanso {$state['descanso_segundos']}s" : '';
        return "{$tipo}  ·  vel. {$vel}  ·  {$dur}{$descanso}";
    }

    private static function ejercicioOptions(): array
    {
        return [
            'circular'     => '⭕ Circular (horario)',
            'circular_ccw' => '🔄 Circular (antihorario)',
            'figure8'      => '∞ Figura 8',
            'figure8_ccw'  => '∞ Figura 8 (inverso)',
            'figure8_v'    => '∞ Figura 8 vertical',
            'horizontal'   => '↔ Horizontal',
            'vertical'     => '↕ Vertical',
            'vertical_rev' => '↕ Vertical (inverso)',
            'diagonal'     => '↗ Diagonal ↖↘',
            'diagonal_tr'  => '↘ Diagonal ↗↙',
            'triangular'   => '🔺 Triangular',
            'square'       => '⬛ Cuadrado',
            'spiral'       => '🌀 Espiral',
            'zigzag'       => '⚡ Zigzag',
            'saccade'      => '👁 Sacádico',
            'spring'       => '〰️ Resorte (gusanito)',
            'particles'    => '✨ Puntos aleatorios',
            'bee_h'        => '🐝 Abeja horizontal (loops L↔R)',
            'bee_v'        => '🐝 Abeja vertical (loops ↑↓)',
            'wave_h'       => '〜 Arco de onda horizontal (∩)',
            'wave_h_inv'   => '〜 Arco de onda horizontal invertido (∪)',
        ];
    }
}
