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
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function planEjercicio(): BelongsTo
    {
        return $this->belongsTo(PlanEjercicio::class, 'plan_ejercicio_id');
    }
}
