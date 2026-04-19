<?php

namespace App\Filament\Resources\ExerciseSessions\Schemas;

use App\Models\LogSesion;
use App\Models\SesionEjercicio;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ExerciseSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                // ── Progreso actual (solo en edición) ─────────────────────────
                Section::make('Progreso de la terapia')
                    ->icon('heroicon-o-chart-bar')
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        Placeholder::make('progreso_detalle')
                            ->label('')
                            ->content(function ($record) {
                                if (! $record) return '';

                                $total       = (int) $record->total_sesiones;
                                $completadas = LogSesion::where('paciente_id', $record->paciente_id)
                                    ->where('plan_ejercicio_id', $record->plan_ejercicio_id)
                                    ->count();
                                $restantes   = max(0, $total - $completadas);

                                $pct      = $total > 0 ? min(100, (int) round($completadas / $total * 100)) : 0;
                                $barWidth = max(1, $pct);

                                [$color, $bgColor, $label] = match (true) {
                                    $pct >= 100 => ['#22c55e', 'rgba(34,197,94,0.12)',  'Completada'],
                                    $pct >= 50  => ['#f59e0b', 'rgba(245,158,11,0.12)', 'En progreso'],
                                    default     => ['#4480ef', 'rgba(239,68,68,0.12)',  'Iniciada'],
                                };

                                $statusDot = "<span style='display:inline-block;width:8px;height:8px;border-radius:50%;background:{$color};margin-right:6px;'></span>";

                                return new HtmlString("
                                    <div style='display:flex;gap:20px;align-items:stretch;flex-wrap:wrap;'>

                                        <!-- Porcentaje grande -->
                                        <div style='display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:110px;padding:16px 20px;border-radius:12px;background:{$bgColor};border:1px solid {$color}33;'>
                                            <span style='font-size:42px;font-weight:800;line-height:1;color:{$color};'>{$pct}%</span>
                                            <span style='margin-top:4px;font-size:11px;font-weight:600;color:{$color};text-transform:uppercase;letter-spacing:0.06em;'>{$statusDot}{$label}</span>
                                        </div>

                                        <!-- Detalle y barra -->
                                        <div style='flex:1;min-width:200px;display:flex;flex-direction:column;justify-content:center;gap:12px;'>

                                            <div style='display:flex;gap:12px;'>
                                                <div style='flex:1;padding:10px 14px;border-radius:8px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);text-align:center;'>
                                                    <div style='font-size:22px;font-weight:700;color:{$color};'>{$completadas}</div>
                                                    <div style='font-size:11px;color:#94a3b8;margin-top:2px;'>completadas</div>
                                                </div>
                                                <div style='flex:1;padding:10px 14px;border-radius:8px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);text-align:center;'>
                                                    <div style='font-size:22px;font-weight:700;color:#94a3b8;'>{$restantes}</div>
                                                    <div style='font-size:11px;color:#94a3b8;margin-top:2px;'>restantes</div>
                                                </div>
                                                <div style='flex:1;padding:10px 14px;border-radius:8px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);text-align:center;'>
                                                    <div style='font-size:22px;font-weight:700;color:#64748b;'>{$total}</div>
                                                    <div style='font-size:11px;color:#94a3b8;margin-top:2px;'>total</div>
                                                </div>
                                            </div>

                                            <!-- Barra de progreso -->
                                            <div>
                                                <div style='width:100%;height:8px;background:rgba(255,255,255,0.08);border-radius:999px;overflow:hidden;'>
                                                    <div style='height:100%;width:{$barWidth}%;background:linear-gradient(90deg,{$color}cc,{$color});border-radius:999px;transition:width 0.5s ease;'></div>
                                                </div>
                                                <div style='display:flex;justify-content:space-between;margin-top:4px;font-size:11px;color:#64748b;'>
                                                    <span>0</span>
                                                    <span>{$total} sesiones</span>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                ");
                            }),
                    ]),

                // ── Asignación ────────────────────────────────────────────────
                Section::make('Asignación')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('paciente_id')
                                ->label('Paciente')
                                ->relationship('paciente', 'nombre')
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(),

                            Select::make('plan_ejercicio_id')
                                ->label('Plan de ejercicio')
                                ->options(fn () => \App\Models\PlanEjercicio::where('activo', true)
                                    ->orderBy('nombre')
                                    ->pluck('nombre', 'id'))
                                ->searchable()
                                ->required()
                                ->live(),
                        ]),
                    ]),

                // ── Programación ──────────────────────────────────────────────
                Section::make('Programación')
                    ->icon('heroicon-o-calendar-days')
                    ->description('Define la frecuencia, el total de sesiones y la fecha de inicio. La fecha de fin se calcula automáticamente.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('veces_por_dia')
                                ->label('Sesiones por día')
                                ->helperText('Cuántas veces al día debe practicar')
                                ->numeric()->default(1)
                                ->minValue(1)->maxValue(10)
                                ->required()->live(),

                            TextInput::make('intervalo_horas')
                                ->label('Intervalo entre sesiones (h)')
                                ->helperText('Horas mínimas de espera entre sesiones del día')
                                ->numeric()->default(8)
                                ->minValue(1)->maxValue(24)
                                ->required(),

                            TextInput::make('frecuencia_dias')
                                ->label('Cada cuántos días')
                                ->helperText('1 = todos los días · 2 = día por medio…')
                                ->numeric()->default(1)
                                ->minValue(1)->maxValue(30)
                                ->required()->live(),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('total_sesiones')
                                ->label('Total de sesiones')
                                ->helperText('Representa el 100 % de la terapia')
                                ->numeric()->default(10)
                                ->minValue(1)->maxValue(500)
                                ->required()->live(),

                            DatePicker::make('fecha_inicio')
                                ->label('Fecha de inicio')
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->live(),

                            // Fecha fin calculada (solo lectura)
                            Placeholder::make('fecha_fin_calculada')
                                ->label('Fecha de fin (calculada)')
                                ->live()
                                ->content(function ($get) {
                                    $inicio         = $get('fecha_inicio');
                                    $total          = (int) $get('total_sesiones');
                                    $vecesPorDia    = max(1, (int) $get('veces_por_dia'));
                                    $frecuenciaDias = max(1, (int) $get('frecuencia_dias'));

                                    if (! $inicio || $total <= 0) {
                                        return new HtmlString('<span class="text-gray-400 text-sm">Completa los campos</span>');
                                    }

                                    $fecha = SesionEjercicio::calcularFechaFin($inicio, $total, $vecesPorDia, $frecuenciaDias);
                                    return new HtmlString(
                                        '<span class="text-base font-semibold text-cyan-400">' . $fecha->format('d/m/Y') . '</span>'
                                    );
                                }),
                        ]),
                    ]),

                // ── Resumen del plan ──────────────────────────────────────────
                Section::make('Ejercicios del plan')
                    ->icon('heroicon-o-eye')
                    ->collapsed()
                    ->visible(fn ($get) => (bool) $get('plan_ejercicio_id'))
                    ->schema([
                        Placeholder::make('resumen_plan')
                            ->label('')
                            ->content(function ($get) {
                                $planId = $get('plan_ejercicio_id');
                                if (! $planId) return '';
                                $plan = \App\Models\PlanEjercicio::with('ejercicios')->find($planId);
                                if (! $plan || $plan->ejercicios->isEmpty()) {
                                    return new HtmlString('
                                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 py-4">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            Este plan no tiene ejercicios configurados.
                                        </div>
                                    ');
                                }

                                $total = $plan->ejercicios->count();
                                $duracionTotal = $plan->ejercicios->sum('duracion');
                                $durTotalStr = $duracionTotal > 0 ? "{$duracionTotal}s" : '—';

                                $rows = $plan->ejercicios->map(function ($ej, $i) {
                                    $dur     = $ej->duracion > 0 ? "{$ej->duracion}s" : '∞';
                                    $descanso = $ej->descanso_segundos > 0 ? "{$ej->descanso_segundos}s" : '—';
                                    $num     = $i + 1;
                                    $bgClass = $i % 2 === 0 ? '' : 'background-color:rgba(255,255,255,0.02)';

                                    return "
                                    <tr style='{$bgClass}'>
                                        <td style='padding:10px 12px;width:36px;'>
                                            <span style='display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:rgba(99,102,241,0.15);color:#818cf8;font-size:11px;font-weight:700;'>{$num}</span>
                                        </td>
                                        <td style='padding:10px 12px;font-size:13px;font-weight:500;color:inherit;'>
                                            {$ej->tipo_ejercicio}
                                        </td>
                                        <td style='padding:10px 12px;'>
                                            <span style='display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#94a3b8;'>
                                                <svg style='width:12px;height:12px;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 10V3L4 14h7v7l9-11h-7z'/></svg>
                                                vel. {$ej->velocidad}
                                            </span>
                                        </td>
                                        <td style='padding:10px 12px;'>
                                            <span style='display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#94a3b8;'>
                                                <svg style='width:12px;height:12px;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'/></svg>
                                                {$dur}
                                            </span>
                                        </td>
                                        <td style='padding:10px 12px;'>
                                            <span style='display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#64748b;'>
                                                <svg style='width:12px;height:12px;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z'/></svg>
                                                pausa {$descanso}
                                            </span>
                                        </td>
                                    </tr>";
                                })->join('');

                                return new HtmlString("
                                    <div style='border-radius:8px;overflow:hidden;border:1px solid rgba(148,163,184,0.15);'>
                                        <table style='width:100%;border-collapse:collapse;'>
                                            <thead>
                                                <tr style='background:rgba(99,102,241,0.08);'>
                                                    <th style='padding:8px 12px;text-align:left;font-size:11px;font-weight:600;color:#818cf8;text-transform:uppercase;letter-spacing:0.05em;width:36px;'>#</th>
                                                    <th style='padding:8px 12px;text-align:left;font-size:11px;font-weight:600;color:#818cf8;text-transform:uppercase;letter-spacing:0.05em;'>Ejercicio</th>
                                                    <th style='padding:8px 12px;text-align:left;font-size:11px;font-weight:600;color:#818cf8;text-transform:uppercase;letter-spacing:0.05em;'>Velocidad</th>
                                                    <th style='padding:8px 12px;text-align:left;font-size:11px;font-weight:600;color:#818cf8;text-transform:uppercase;letter-spacing:0.05em;'>Duración</th>
                                                    <th style='padding:8px 12px;text-align:left;font-size:11px;font-weight:600;color:#818cf8;text-transform:uppercase;letter-spacing:0.05em;'>Pausa</th>
                                                </tr>
                                            </thead>
                                            <tbody style='border-top:1px solid rgba(148,163,184,0.1);'>
                                                {$rows}
                                            </tbody>
                                            <tfoot>
                                                <tr style='background:rgba(148,163,184,0.05);border-top:1px solid rgba(148,163,184,0.15);'>
                                                    <td colspan='5' style='padding:8px 12px;'>
                                                        <div style='display:flex;gap:16px;font-size:12px;color:#64748b;'>
                                                            <span><strong style='color:#94a3b8;'>{$total}</strong> ejercicios</span>
                                                            <span><strong style='color:#94a3b8;'>{$durTotalStr}</strong> duración total</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                ");
                            }),
                    ]),

                // ── Observaciones ──────────────────────────────────────────────
                Section::make('Observaciones')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        Textarea::make('observaciones')
                            ->label('')
                            ->rows(3)
                            ->placeholder('Comportamiento del paciente, dificultades, logros...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
