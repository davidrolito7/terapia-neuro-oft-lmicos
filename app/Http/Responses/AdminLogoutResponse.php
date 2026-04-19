<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AdminLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Solo cierra la sesión del guard admin, sin tocar el guard web (pacientes)
        Auth::guard('admin')->logout();
        $request->session()->regenerate(true); // nuevo ID de sesión, conserva el resto de datos
        $request->session()->regenerateToken();

        return redirect()->to(Filament::getPanel('admin')->getLoginUrl());
    }
}
