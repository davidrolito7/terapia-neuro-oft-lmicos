<?php

namespace App\Http\Controllers;

use App\Models\EjercicioPlan;
use App\Models\ProgresoEjercicio;
use App\Models\SesionEjercicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExerciseProgressController extends Controller
{
    public function marcar(Request $request, int $ejercicioPlanId): RedirectResponse
    {
        $paciente = $request->user()->paciente;

        if (! $paciente) {
            return redirect()->back();
        }

        // Verificar que el ejercicio pertenece a un plan asignado al paciente via sesión
        $ejercicio = EjercicioPlan::whereHas('plan', fn ($q) => $q->where('activo', true))
            ->whereHas('plan.sesiones', fn ($q) => $q->where('paciente_id', $paciente->id))
            ->find($ejercicioPlanId);

        if ($ejercicio) {
            $progreso = ProgresoEjercicio::firstOrCreate([
                'paciente_id'       => $paciente->id,
                'ejercicio_plan_id' => $ejercicioPlanId,
            ]);
            // Refrescar updated_at en cada reproducción para calcular próxima sesión
            $progreso->touch();
        }

        return redirect()->back();
    }
}
