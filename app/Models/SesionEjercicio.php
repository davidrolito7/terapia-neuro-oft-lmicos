<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionEjercicio extends Model
{
    protected $table = 'sesiones_ejercicio';

    protected $fillable = [
        'paciente_id',
        'user_id',
        'plan_ejercicio_id',
        'veces_por_dia',
        'intervalo_horas',
        'frecuencia_dias',
        'total_sesiones',
        'fecha_inicio',
        'fecha_fin',
        'porcentaje_avance',
        'completada',
        'observaciones',
        'iniciada_en',
        'finalizada_en',
    ];

    protected $casts = [
        'completada'        => 'boolean',
        'porcentaje_avance' => 'integer',
        'veces_por_dia'   => 'integer',
        'intervalo_horas' => 'integer',
        'frecuencia_dias' => 'integer',
        'total_sesiones'  => 'integer',
        'fecha_inicio'    => 'date',
        'fecha_fin'         => 'date',
        'iniciada_en'       => 'datetime',
        'finalizada_en'     => 'datetime',
    ];

    /**
     * Calcula la fecha de fin dado el conjunto de parámetros de programación.
     * diasValidos  = ceil(total_sesiones / veces_por_dia)
     * diasAgregar  = (diasValidos - 1) * frecuencia_dias
     * fecha_fin    = fecha_inicio + diasAgregar
     */
    public static function calcularFechaFin(
        string $fechaInicio,
        int $totalSesiones,
        int $vecesPorDia,
        int $frecuenciaDias
    ): \Carbon\Carbon {
        $vecesPorDia   = max(1, $vecesPorDia);
        $frecuenciaDias = max(1, $frecuenciaDias);
        $diasValidos   = (int) ceil($totalSesiones / $vecesPorDia);
        $diasAgregar   = ($diasValidos - 1) * $frecuenciaDias;
        return \Carbon\Carbon::parse($fechaInicio)->addDays($diasAgregar);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function planEjercicio(): BelongsTo
    {
        return $this->belongsTo(PlanEjercicio::class, 'plan_ejercicio_id');
    }
}
