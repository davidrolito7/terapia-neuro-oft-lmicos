<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PatientAuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/PatientLogin');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'telefono'         => ['required', 'string'],
            'fecha_nacimiento' => ['required', 'date'],
        ]);

        $paciente = Paciente::where('telefono', $request->telefono)
            ->whereDate('fecha_nacimiento', $request->fecha_nacimiento)
            ->whereNotNull('paciente_user_id')
            ->first();

        if (! $paciente || ! $paciente->pacienteUsuario) {
            throw ValidationException::withMessages([
                'telefono' => 'Los datos ingresados no corresponden a ningún paciente registrado.',
            ]);
        }

        Auth::login($paciente->pacienteUsuario, false);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
