<?php

use App\Http\Controllers\Auth\PatientAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExercisesController;
use App\Http\Controllers\ExerciseProgressController;
use App\Http\Controllers\SesionLogController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

// Patient login (phone + birthdate)
Route::middleware('guest')->group(function () {
    Route::get('/paciente/login', [PatientAuthController::class, 'create'])
        ->name('paciente.login');
    Route::post('/paciente/login', [PatientAuthController::class, 'store'])
        ->name('paciente.login.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/exercises', [ExercisesController::class, 'index'])->name('exercises.index');
    Route::post('/ejercicios/{id}/completar', [ExerciseProgressController::class, 'marcar'])
        ->name('ejercicios.completar');
    Route::post('/sesion/completada', [SesionLogController::class, 'store'])
        ->name('sesion.completada');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
