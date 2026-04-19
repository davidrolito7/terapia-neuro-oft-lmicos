<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progresos_ejercicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('ejercicio_plan_id')->constrained('ejercicios_plan')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['paciente_id', 'ejercicio_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progresos_ejercicio');
    }
};
