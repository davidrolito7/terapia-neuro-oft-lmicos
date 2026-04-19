<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'telefono'         => ['required', 'string'],
            'fecha_nacimiento' => ['required', 'date'],
        ]);

        $throttleKey = Str::lower($request->string('telefono')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'telefono' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $paciente = Paciente::where('telefono', $request->telefono)
            ->whereDate('fecha_nacimiento', $request->fecha_nacimiento)
            ->whereNotNull('paciente_user_id')
            ->first();

        if (! $paciente || ! $paciente->pacienteUsuario) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'telefono' => 'Los datos ingresados no corresponden a ningún paciente registrado.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($paciente->pacienteUsuario, false);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Regenerar ID de sesión sin destruirla, para no cerrar la sesión de admin
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
