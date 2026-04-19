<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgresoEjercicio extends Model
{
    protected $table = 'progresos_ejercicio';

    protected $fillable = [
        'paciente_id',
        'ejercicio_plan_id',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function ejercicioPlan(): BelongsTo
    {
        return $this->belongsTo(EjercicioPlan::class, 'ejercicio_plan_id');
    }
}
