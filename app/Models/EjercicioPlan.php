<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EjercicioPlan extends Model
{
    protected $table = 'ejercicios_plan';

    protected $fillable = [
        'plan_ejercicio_id',
        'orden',
        'tipo_ejercicio',
        'tipo_estimulo',
        'emoji_estimulo',
        'velocidad',
        'tamano',
        'color',
        'duracion',
        'descanso_segundos',
        'notas',
    ];

    protected $casts = [
        'velocidad'         => 'integer',
        'tamano'            => 'integer',
        'duracion'          => 'integer',
        'descanso_segundos' => 'integer',
        'orden'             => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanEjercicio::class, 'plan_ejercicio_id');
    }
}
