<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_sesion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('plan_ejercicio_id')->constrained('planes_ejercicio')->cascadeOnDelete();
            $table->timestamps(); // created_at = cuándo completó la sesión completa
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_sesion');
    }
};
