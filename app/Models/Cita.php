<?php

namespace App\Models;

use App\Mail\RecordatorioCita;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'paciente_id',
        'user_id',
        'inicio',
        'duracion_minutos',
        'estado',
        'observaciones',
        'color',
        'tipo_recurrencia',
        'intervalo_recurrencia',
        'dias_semana',
        'fin_recurrencia',
        'cita_padre_id',
    ];

    private static array $coloresPaleta = [
        '#60a5fa', // azul
        '#34d399', // esmeralda
        '#f472b6', // rosa
        '#a78bfa', // violeta
        '#fbbf24', // ámbar
        '#fb923c', // naranja
        '#22d3ee', // cian
        '#f87171', // rojo suave
    ];

    public static function colorPorPaciente(int $pacienteId): string
    {
        return self::$coloresPaleta[$pacienteId % count(self::$coloresPaleta)];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Cita $cita) {
            if (empty($cita->color) && $cita->paciente_id) {
                $cita->color = self::colorPorPaciente((int) $cita->paciente_id);
            }
        });

        static::created(function (Cita $cita) {
            if ($cita->inicio->toDateString() !== now()->addDay()->toDateString()) {
                return;
            }

            $cita->loadMissing('paciente');

            if (!$cita->paciente?->email) {
                return;
            }

            try {
                Mail::to($cita->paciente->email)->send(new RecordatorioCita($cita));
            } catch (\Throwable) {
                // No interrumpir la creación si el correo falla
            }
        });
    }

    protected $casts = [
        'inicio'         => 'datetime',
        'fin_recurrencia' => 'date',
        'dias_semana'    => 'array',
    ];

    public function getFinAttribute(): Carbon
    {
        return $this->inicio->copy()->addMinutes($this->duracion_minutos);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function terapeuta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function citaPadre(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_padre_id');
    }

    public function recurrencias(): HasMany
    {
        return $this->hasMany(Cita::class, 'cita_padre_id');
    }

    public function generarRecurrencias(): void
    {
        if ($this->tipo_recurrencia === 'ninguna') {
            return;
        }

        $fechaLimite = $this->fin_recurrencia
            ? Carbon::parse($this->fin_recurrencia)->endOfDay()
            : $this->inicio->copy()->addMonths(6);

        $base = [
            'paciente_id'          => $this->paciente_id,
            'user_id'              => $this->user_id,
            'duracion_minutos'     => $this->duracion_minutos,
            'color'                => $this->color,
            'tipo_recurrencia'     => 'ninguna',
            'intervalo_recurrencia' => 1,
            'cita_padre_id'        => $this->id,
        ];

        if ($this->tipo_recurrencia === 'semanal' && !empty($this->dias_semana)) {
            $this->generarSemanal($base, $fechaLimite);
            return;
        }

        $fecha = $this->inicio->copy();
        $max   = 365;
        $i     = 0;

        while ($i++ < $max) {
            match ($this->tipo_recurrencia) {
                'diaria'  => $fecha->addDays($this->intervalo_recurrencia),
                'semanal' => $fecha->addWeeks($this->intervalo_recurrencia),
                'mensual' => $fecha->addMonths($this->intervalo_recurrencia),
                default   => null,
            };

            if ($fecha->gt($fechaLimite)) break;

            static::create(array_merge($base, ['inicio' => $fecha->copy()]));
        }
    }

    private function generarSemanal(array $base, Carbon $fechaLimite): void
    {
        // dias_semana: 1=lunes … 7=domingo (ISO weekday)
        $dias   = array_map('intval', $this->dias_semana);
        $semana = $this->inicio->copy()->startOfWeek()->addWeeks($this->intervalo_recurrencia);
        $max    = 260; // ~5 años
        $i      = 0;

        while ($i++ < $max && $semana->lte($fechaLimite)) {
            foreach ($dias as $iso) {
                $fecha = $semana->copy()
                    ->addDays($iso - 1)
                    ->setTime($this->inicio->hour, $this->inicio->minute);

                if ($fecha->gt($this->inicio) && $fecha->lte($fechaLimite)) {
                    static::create(array_merge($base, ['inicio' => $fecha]));
                }
            }
            $semana->addWeeks($this->intervalo_recurrencia);
        }
    }
}
