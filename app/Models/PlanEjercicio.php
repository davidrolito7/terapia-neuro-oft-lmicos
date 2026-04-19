<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanEjercicio extends Model
{
    protected $table = 'planes_ejercicio';

    protected $fillable = [
        'paciente_id', // nullable — los planes son plantillas reutilizables
        'user_id',
        'nombre',
        'activo',
        'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ejercicios(): HasMany
    {
        return $this->hasMany(EjercicioPlan::class, 'plan_ejercicio_id')->orderBy('orden');
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionEjercicio::class, 'plan_ejercicio_id');
    }
}
