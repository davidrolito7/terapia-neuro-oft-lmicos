<?php

namespace App\Http\Controllers;

use App\Models\LogSesion;
use App\Models\ProgresoEjercicio;
use App\Models\SesionEjercicio;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user     = $request->user();
        $paciente = $user->paciente;

        if (! $paciente) {
            return Inertia::render('Dashboard', [
                'plan'          => null,
                'ejercicios'    => [],
                'porcentaje'    => 0,
                'proximaSesion' => null,
            ]);
        }

        // El plan se obtiene a través de la sesión asignada al paciente
        $sesionActiva = SesionEjercicio::where('paciente_id', $paciente->id)
            ->whereNotNull('plan_ejercicio_id')
            ->whereHas('planEjercicio', fn ($q) => $q->where('activo', true))
            ->latest()
            ->first();

        $plan = $sesionActiva?->planEjercicio()->with('ejercicios')->first();

        if (! $plan) {
            return Inertia::render('Dashboard', [
                'plan'          => null,
                'ejercicios'    => [],
                'porcentaje'    => 0,
                'proximaSesion' => null,
            ]);
        }

        $ejercicioIds = $plan->ejercicios->pluck('id');

        $completadosIds = ProgresoEjercicio::where('paciente_id', $paciente->id)
            ->whereIn('ejercicio_plan_id', $ejercicioIds)
            ->pluck('ejercicio_plan_id')
            ->flip()
            ->all();

        $ejercicios = $plan->ejercicios->map(fn ($ej) => [
            'id'             => $ej->id,
            'orden'          => $ej->orden,
            'tipo_ejercicio' => $ej->tipo_ejercicio,
            'tipo_estimulo'  => $ej->tipo_estimulo,
            'emoji_estimulo' => $ej->emoji_estimulo,
            'velocidad'      => $ej->velocidad,
            'tamano'         => $ej->tamano,
            'color'          => $ej->color,
            'duracion'       => $ej->duracion,
            'notas'          => $ej->notas,
            'completado'     => array_key_exists($ej->id, $completadosIds),
        ]);

        $total       = $ejercicios->count();
        $completados = $ejercicios->filter(fn ($e) => $e['completado'])->count();
        $porcentaje  = $total > 0 ? (int) round($completados / $total * 100) : 0;

        // Pasar la sesión ya encontrada para evitar queries inconsistentes
        $proximaSesion = $this->calcularProximaSesion($paciente->id, $sesionActiva);

        return Inertia::render('Dashboard', [
            'plan'          => ['id' => $plan->id, 'nombre' => $plan->nombre],
            'ejercicios'    => $ejercicios->values(),
            'porcentaje'    => $porcentaje,
            'proximaSesion' => $proximaSesion,
        ]);
    }

    private function calcularProximaSesion(int $pacienteId, SesionEjercicio $sesion): ?array
    {
        $planId = $sesion->plan_ejercicio_id;

        // ── ¿Todavía no ha comenzado? ─────────────────────────────────────
        if ($sesion->fecha_inicio && $sesion->fecha_inicio->isAfter(today())) {
            return [
                'tipo'       => 'proximo_dia',
                'mensaje'    => 'Tu terapia comienza el ' . $sesion->fecha_inicio->format('d/m/Y'),
                'disponible' => false,
            ];
        }

        // ── ¿Ya expiró el plan? ───────────────────────────────────────────
        if ($sesion->fecha_fin && $sesion->fecha_fin->isBefore(today())) {
            return [
                'tipo'       => 'proximo_dia',
                'mensaje'    => 'Tu plan de terapia ha concluido',
                'disponible' => false,
            ];
        }

        // ── ¿Hoy es un día válido según frecuencia? ───────────────────────
        if ($sesion->fecha_inicio && $sesion->frecuencia_dias > 1) {
            $diasDesdeInicio = (int) $sesion->fecha_inicio->diffInDays(today());
            if ($diasDesdeInicio % $sesion->frecuencia_dias !== 0) {
                $diasHastaSiguiente = $sesion->frecuencia_dias - ($diasDesdeInicio % $sesion->frecuencia_dias);
                return [
                    'tipo'       => 'proximo_dia',
                    'mensaje'    => 'Próxima sesión en ' . $diasHastaSiguiente . ' ' . ($diasHastaSiguiente === 1 ? 'día' : 'días'),
                    'disponible' => false,
                ];
            }
        }

        // ── Sesiones completadas hoy ──────────────────────────────────────
        $sesionesHoy = LogSesion::where('paciente_id', $pacienteId)
            ->where('plan_ejercicio_id', $planId)
            ->whereDate('created_at', today())
            ->count();

        if ($sesionesHoy >= $sesion->veces_por_dia) {
            $diasHastaSiguiente = $sesion->frecuencia_dias;
            return [
                'tipo'       => 'proximo_dia',
                'mensaje'    => 'Sesiones de hoy completadas · Próxima en ' . $diasHastaSiguiente . ' ' . ($diasHastaSiguiente === 1 ? 'día' : 'días'),
                'disponible' => false,
            ];
        }

        // ── ¿Hay que esperar el intervalo entre sesiones del mismo día? ────
        $ultimoLog = LogSesion::where('paciente_id', $pacienteId)
            ->where('plan_ejercicio_id', $planId)
            ->whereDate('created_at', today())
            ->latest()
            ->first();

        if ($ultimoLog) {
            $proximaHora = $ultimoLog->created_at->addHours($sesion->intervalo_horas);

            if ($proximaHora->isFuture()) {
                $diff    = (int) now()->diffInMinutes($proximaHora);
                $horas   = (int) floor($diff / 60);
                $minutos = $diff % 60;
                $mensaje = $horas > 0
                    ? "Próxima sesión en {$horas}h" . ($minutos > 0 ? " {$minutos}min" : '')
                    : "Próxima sesión en {$minutos} min";

                return [
                    'tipo'       => 'espera',
                    'mensaje'    => $mensaje,
                    'disponible' => false,
                ];
            }
        }

        $pendientes = $sesion->veces_por_dia - $sesionesHoy;
        return [
            'tipo'       => 'disponible',
            'mensaje'    => $pendientes > 1 ? "Disponible · {$pendientes} sesiones pendientes hoy" : 'Disponible ahora',
            'disponible' => true,
        ];
    }
}
