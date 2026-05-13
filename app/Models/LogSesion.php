<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogSesion extends Model
{
    protected $table = 'logs_sesion';

    protected $fillable = [
        'paciente_id',
        'plan_ejercicio_id',
        'sesion_ejercicio_id',
        'calificacion',
        'observaciones',
        'ardio_ojo',
        'mas_ejercicios',
        'siguio_todos_objetos',
        'ejercicio_no_siguio',
        'orden_objetos',
        'cansancio_vista',
    ];

    protected $casts = [
        'ardio_ojo'             => 'boolean',
        'mas_ejercicios'        => 'boolean',
        'siguio_todos_objetos'  => 'boolean',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function planEjercicio(): BelongsTo
    {
        return $this->belongsTo(PlanEjercicio::class, 'plan_ejercicio_id');
    }

    public function sesionEjercicio(): BelongsTo
    {
        return $this->belongsTo(SesionEjercicio::class, 'sesion_ejercicio_id');
    }
}
