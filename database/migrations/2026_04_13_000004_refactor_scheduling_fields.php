<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quitar campos de programación del ejercicio individual
        Schema::table('ejercicios_plan', function (Blueprint $table) {
            $table->dropColumn(['repeticiones', 'frecuencia_dias', 'veces_por_dia', 'descanso_segundos']);
        });

        // Agregar campos de programación a la sesión
        Schema::table('sesiones_ejercicio', function (Blueprint $table) {
            $table->unsignedTinyInteger('veces_por_dia')->default(1)->after('plan_ejercicio_id');
            $table->unsignedTinyInteger('intervalo_horas')->default(8)->after('veces_por_dia');
            $table->unsignedTinyInteger('frecuencia_dias')->default(1)->after('intervalo_horas');
            $table->date('fecha_inicio')->nullable()->after('frecuencia_dias');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
        });
    }

    public function down(): void
    {
        Schema::table('sesiones_ejercicio', function (Blueprint $table) {
            $table->dropColumn(['veces_por_dia', 'intervalo_horas', 'frecuencia_dias', 'fecha_inicio', 'fecha_fin']);
        });

        Schema::table('ejercicios_plan', function (Blueprint $table) {
            $table->unsignedTinyInteger('repeticiones')->default(1);
            $table->unsignedTinyInteger('frecuencia_dias')->default(1);
            $table->unsignedTinyInteger('veces_por_dia')->default(1);
            $table->unsignedSmallInteger('descanso_segundos')->default(30);
        });
    }
};
