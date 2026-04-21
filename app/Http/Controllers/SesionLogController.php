<?php

namespace App\Http\Controllers;

use App\Models\LogSesion;
use App\Models\SesionEjercicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SesionLogController extends Controller
{
    /**
     * Se llama cuando el paciente termina TODOS los ejercicios de la secuencia.
     */
    public function store(Request $request): RedirectResponse
    {
        $paciente = $request->user()->paciente;

        if (! $paciente) {
            return redirect()->back();
        }

        $validated = $request->validate([
            'calificacion'  => ['nullable', 'in:bueno,regular,malo'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $sesionActiva = SesionEjercicio::where('paciente_id', $paciente->id)
            ->whereNotNull('plan_ejercicio_id')
            ->whereHas('planEjercicio', fn ($q) => $q->where('activo', true))
            ->latest()
            ->first();

        if ($sesionActiva) {
            LogSesion::create([
                'paciente_id'         => $paciente->id,
                'plan_ejercicio_id'   => $sesionActiva->plan_ejercicio_id,
                'sesion_ejercicio_id' => $sesionActiva->id,
                'calificacion'        => $validated['calificacion'] ?? null,
                'observaciones'       => $validated['observaciones'] ?? null,
            ]);
        }

        return redirect()->route('dashboard');
    }
}
