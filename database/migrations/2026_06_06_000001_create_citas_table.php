<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('inicio');
            $table->unsignedSmallInteger('duracion_minutos')->default(60);
            $table->string('estado', 20)->default('pendiente'); // pendiente, atendida, cancelada
            $table->text('observaciones')->nullable();
            $table->string('color', 20)->default('#b8dff0');
            $table->string('tipo_recurrencia', 20)->default('ninguna'); // ninguna, diaria, semanal, mensual
            $table->unsignedTinyInteger('intervalo_recurrencia')->default(1);
            $table->json('dias_semana')->nullable(); // ISO days: 1=lunes…7=domingo
            $table->date('fin_recurrencia')->nullable();
            $table->foreignId('cita_padre_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->timestamps();

            $table->index(['paciente_id', 'inicio']);
            $table->index(['inicio', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
