<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs_sesion', function (Blueprint $table) {
            $table->foreignId('sesion_ejercicio_id')
                ->nullable()
                ->after('plan_ejercicio_id')
                ->constrained('sesiones_ejercicio')
                ->nullOnDelete();

            $table->enum('calificacion', ['bueno', 'regular', 'malo'])
                ->nullable()
                ->after('sesion_ejercicio_id');

            $table->text('observaciones')
                ->nullable()
                ->after('calificacion');
        });
    }

    public function down(): void
    {
        Schema::table('logs_sesion', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sesion_ejercicio_id');
            $table->dropColumn(['calificacion', 'observaciones']);
        });
    }
};
