<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioCita;
use App\Models\Cita;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatoriosCitas extends Command
{
    protected $signature = 'citas:recordatorios {--dry-run : Muestra las citas sin enviar correos}';

    protected $description = 'Envía recordatorios por correo a los pacientes con cita mañana';

    public function handle(): int
    {
        $manana = now()->addDays(2);
        
        $this->info('Ahora Laravel: ' . now()->format('Y-m-d H:i:s T'));
        $this->info('Buscando citas para: ' . $manana->toDateString());

        $totalManana = Cita::whereDate('inicio', $manana->toDateString())->count();

        $totalConEmail = Cita::whereDate('inicio', $manana->toDateString())
            ->whereHas(
                'paciente',
                fn($q) => $q
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
            )
            ->count();

        $totalEstadoValido = Cita::whereDate('inicio', $manana->toDateString())
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->count();

        $this->info('Total citas mañana: ' . $totalManana);
        $this->info('Total citas mañana con email: ' . $totalConEmail);
        $this->info('Total citas mañana con estado válido: ' . $totalEstadoValido);

        $citas = Cita::with(['paciente', 'terapeuta'])
            ->whereDate('inicio', $manana->toDateString())
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->whereHas(
                'paciente',
                fn($q) => $q
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
            )
            ->get();

        if ($citas->isEmpty()) {
            $this->info('No hay citas mañana con correo de paciente registrado.');
            return self::SUCCESS;
        }

        $dry = $this->option('dry-run');

        $this->table(
            ['Paciente', 'Email', 'Hora', 'Estado'],
            $citas->map(fn($c) => [
                $c->paciente->nombre_completo,
                $c->paciente->email,
                $c->inicio->format('H:i'),
                $c->estado ?? 'programada',
            ])
        );

        if ($dry) {
            $this->warn('Modo --dry-run: no se enviaron correos.');
            return self::SUCCESS;
        }

        $enviados = 0;
        $errores  = 0;

        foreach ($citas as $cita) {
            try {
                Mail::to($cita->paciente->email)->send(new RecordatorioCita($cita));
                $this->line(" ✓ Enviado a {$cita->paciente->email}");
                $enviados++;
            } catch (\Throwable $e) {
                $this->error(" ✗ Error con {$cita->paciente->email}: {$e->getMessage()}");
                $errores++;
            }
        }

        $this->info("Recordatorios enviados: {$enviados}. Errores: {$errores}.");

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }
}
