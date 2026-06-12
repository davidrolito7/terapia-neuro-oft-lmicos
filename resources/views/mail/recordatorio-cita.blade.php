<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de cita</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #1e40af; padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; }
        .header p { color: #bfdbfe; margin: 8px 0 0; font-size: 14px; }
        .body { padding: 40px; }
        .greeting { font-size: 18px; color: #1e293b; margin-bottom: 16px; }
        .message { color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .card { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 24px; margin-bottom: 24px; }
        .card-row { display: flex; align-items: center; margin-bottom: 12px; }
        .card-row:last-child { margin-bottom: 0; }
        .label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; width: 120px; flex-shrink: 0; }
        .value { font-size: 15px; color: #0f172a; font-weight: 600; }
        .note { background: #fefce8; border-left: 4px solid #fbbf24; padding: 12px 16px; font-size: 14px; color: #713f12; border-radius: 0 4px 4px 0; margin-bottom: 24px; }
        .footer { background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 13px; margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Neuro Rehabilitación Visual JGM</h1>
            <p>Recordatorio de cita</p>
        </div>

        <div class="body">
            <p class="greeting">Hola, {{ $cita->paciente->nombre_completo }}.</p>

            <p class="message">
                Te recordamos que tienes una cita programada para <strong>mañana</strong>.
                Por favor preséntate a tiempo y trae cualquier documentación o estudio previo que se te haya indicado.
            </p>

            <div class="card">
                <div class="card-row">
                    <span class="label">Fecha</span>
                    <span class="value">{{ $cita->inicio->translatedFormat('l d \d\e F \d\e Y') }}</span>
                </div>
                <div class="card-row">
                    <span class="label">Hora</span>
                    <span class="value">{{ $cita->inicio->format('H:i') }} hrs</span>
                </div>
                <div class="card-row">
                    <span class="label">Duración</span>
                    <span class="value">{{ $cita->duracion_minutos }} minutos</span>
                </div>
                @if($cita->terapeuta)
                <div class="card-row">
                    <span class="label">Terapeuta</span>
                    <span class="value">{{ $cita->terapeuta->name }}</span>
                </div>
                @endif
                @if($cita->observaciones)
                <div class="card-row">
                    <span class="label">Notas</span>
                    <span class="value" style="font-weight:normal;">{{ $cita->observaciones }}</span>
                </div>
                @endif
            </div>

            <div class="note">
                Si necesitas cancelar o reprogramar tu cita, comunícate con nosotros lo antes posible al número de contacto de la clínica.
            </div>
        </div>

        <div class="footer">
            <p>Neuro Rehabilitación Visual JGM &bull; Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>
