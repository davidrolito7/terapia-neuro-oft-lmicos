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
            'calificacion'          => ['nullable', 'in:bueno,regular,malo'],
            'observaciones'         => ['nullable', 'string', 'max:1000'],
            'ardio_ojo'             => ['nullable', 'boolean'],
            'mas_ejercicios'        => ['nullable', 'boolean'],
            'siguio_todos_objetos'  => ['nullable', 'boolean'],
            'ejercicio_no_siguio'   => ['nullable', 'string', 'max:100'],
            'orden_objetos'         => ['nullable', 'string', 'max:1000'],
            'cansancio_vista'       => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        $sesionActiva = SesionEjercicio::where('paciente_id', $paciente->id)
            ->whereNotNull('plan_ejercicio_id')
            ->whereHas('planEjercicio', fn ($q) => $q->where('activo', true))
            ->latest()
            ->first();

        if ($sesionActiva) {
            LogSesion::create([
                'paciente_id'           => $paciente->id,
                'plan_ejercicio_id'     => $sesionActiva->plan_ejercicio_id,
                'sesion_ejercicio_id'   => $sesionActiva->id,
                'calificacion'          => $validated['calificacion'] ?? null,
                'observaciones'         => $validated['observaciones'] ?? null,
                'ardio_ojo'             => isset($validated['ardio_ojo'])            ? (bool) $validated['ardio_ojo']            : null,
                'mas_ejercicios'        => isset($validated['mas_ejercicios'])       ? (bool) $validated['mas_ejercicios']       : null,
                'siguio_todos_objetos'  => isset($validated['siguio_todos_objetos']) ? (bool) $validated['siguio_todos_objetos'] : null,
                'ejercicio_no_siguio'   => $validated['ejercicio_no_siguio'] ?? null,
                'orden_objetos'         => $validated['orden_objetos'] ?? null,
                'cansancio_vista'       => isset($validated['cansancio_vista']) ? (int) $validated['cansancio_vista'] : null,
            ]);
        }

        return redirect()->route('dashboard');
    }
}
