<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs_sesion', function (Blueprint $table) {
            $table->boolean('ardio_ojo')->nullable()->after('observaciones');
            $table->boolean('mas_ejercicios')->nullable()->after('ardio_ojo');
            $table->boolean('siguio_todos_objetos')->nullable()->after('mas_ejercicios');
            $table->string('ejercicio_no_siguio')->nullable()->after('siguio_todos_objetos');
            $table->text('orden_objetos')->nullable()->after('ejercicio_no_siguio');
            $table->tinyInteger('cansancio_vista')->nullable()->after('orden_objetos');
        });
    }

    public function down(): void
    {
        Schema::table('logs_sesion', function (Blueprint $table) {
            $table->dropColumn([
                'ardio_ojo',
                'mas_ejercicios',
                'siguio_todos_objetos',
                'ejercicio_no_siguio',
                'orden_objetos',
                'cansancio_vista',
            ]);
        });
    }
};