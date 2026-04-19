<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejercicios_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_ejercicio_id')->constrained('planes_ejercicio')->cascadeOnDelete();
            $table->unsignedTinyInteger('orden')->default(1);

            $table->string('tipo_ejercicio');
            $table->string('tipo_estimulo');
            $table->string('emoji_estimulo', 10)->nullable();
            $table->unsignedTinyInteger('velocidad')->default(5);
            $table->unsignedTinyInteger('tamano')->default(20);
            $table->string('color', 7)->default('#22d3ee');
            $table->unsignedSmallInteger('duracion')->default(60);       // seg, 0 = sin límite
            $table->unsignedTinyInteger('repeticiones')->default(1);
            $table->unsignedTinyInteger('frecuencia_dias')->default(1);
            $table->unsignedTinyInteger('veces_por_dia')->default(1);
            $table->unsignedSmallInteger('descanso_segundos')->default(30);
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('sesiones_ejercicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_ejercicio_id')->constrained('planes_ejercicio')->cascadeOnDelete();
            $table->unsignedTinyInteger('porcentaje_avance')->default(0); // 0–100
            $table->boolean('completada')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamp('iniciada_en')->nullable();
            $table->timestamp('finalizada_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_ejercicio');
        Schema::dropIfExists('ejercicios_plan');
    }
};
