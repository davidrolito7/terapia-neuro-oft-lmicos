<?php

namespace App\Http\Controllers;

use App\Models\LogSesion;
use App\Models\ProgresoEjercicio;
use App\Models\SesionEjercicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExercisesController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $paciente = $request->user()->paciente;

        // Sin perfil de paciente → dashboard
        if (! $paciente) {
            return redirect()->route('dashboard');
        }

        // El plan se obtiene a través de la sesión asignada al paciente
        $sesionActiva = SesionEjercicio::where('paciente_id', $paciente->id)
            ->whereNotNull('plan_ejercicio_id')
            ->whereHas('planEjercicio', fn ($q) => $q->where('activo', true))
            ->latest()
            ->first();

        $plan = $sesionActiva?->planEjercicio()->with('ejercicios')->first();

        // Sin plan activo → dashboard
        if (! $plan || $plan->ejercicios->isEmpty()) {
            return redirect()->route('dashboard');
        }

        // Verificar disponibilidad usando la misma sesión ya encontrada
        if (! $this->sesionDisponible($paciente->id, $sesionActiva)) {
            return redirect()->route('dashboard');
        }

        $ejercicios = $plan->ejercicios->map(fn ($ej) => [
            'id'                => $ej->id,
            'tipo_ejercicio'    => $ej->tipo_ejercicio,
            'tipo_estimulo'     => $ej->tipo_estimulo,
            'emoji_estimulo'    => $ej->emoji_estimulo,
            'velocidad'         => $ej->velocidad,
            'tamano'            => $ej->tamano,
            'color'             => $ej->color,
            'duracion'          => $ej->duracion,
            'descanso_segundos' => $ej->descanso_segundos,
        ])->values();

        // Iniciar desde un ejercicio específico si se indica
        if ($request->filled('start_from_id')) {
            $idx = $ejercicios->search(fn ($e) => $e['id'] === $request->integer('start_from_id'));
            if ($idx !== false) {
                $ejercicios = $ejercicios->slice($idx)->values();
            }
        }

        return Inertia::render('Exercises/Index', ['planItems' => $ejercicios]);
    }

    /**
     * Determina si el paciente puede iniciar una sesión ahora mismo.
     * Recibe la SesionEjercicio ya encontrada para evitar queries inconsistentes.
     */
    public static function sesionDisponible(int $pacienteId, ?SesionEjercicio $sesion): bool
    {
        // Sin sesión configurada → siempre disponible
        if (! $sesion) {
            return true;
        }

        $planId = $sesion->plan_ejercicio_id;

        // ¿Todavía no ha comenzado?
        if ($sesion->fecha_inicio && $sesion->fecha_inicio->isAfter(today())) {
            return false;
        }

        // ¿Ya expiró?
        if ($sesion->fecha_fin && $sesion->fecha_fin->isBefore(today())) {
            return false;
        }

        // ¿Hoy es un día válido según frecuencia?
        if ($sesion->fecha_inicio && $sesion->frecuencia_dias > 1) {
            $diasDesdeInicio = (int) $sesion->fecha_inicio->diffInDays(today());
            if ($diasDesdeInicio % $sesion->frecuencia_dias !== 0) {
                return false;
            }
        }

        // ¿Ya completó todas las sesiones del día?
        $sesionesHoy = LogSesion::where('paciente_id', $pacienteId)
            ->where('plan_ejercicio_id', $planId)
            ->whereDate('created_at', today())
            ->count();

        if ($sesionesHoy >= $sesion->veces_por_dia) {
            return false;
        }

        // ¿Hay que esperar el intervalo entre sesiones del mismo día?
        $ultimoLog = LogSesion::where('paciente_id', $pacienteId)
            ->where('plan_ejercicio_id', $planId)
            ->whereDate('created_at', today())
            ->latest()
            ->first();

        if ($ultimoLog) {
            $proximaHora = $ultimoLog->created_at->addHours($sesion->intervalo_horas);
            if ($proximaHora->isFuture()) {
                return false;
            }
        }

        return true;
    }
}
