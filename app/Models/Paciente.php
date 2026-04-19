<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = [
        'user_id',
        'paciente_user_id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'direccion',
        'fecha_nacimiento',
        'notas',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pacienteUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paciente_user_id');
    }

    public function progresos(): HasMany
    {
        return $this->hasMany(ProgresoEjercicio::class, 'paciente_id');
    }

    public function planesEjercicio(): HasMany
    {
        return $this->hasMany(PlanEjercicio::class, 'paciente_id');
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionEjercicio::class, 'paciente_id');
    }
}
