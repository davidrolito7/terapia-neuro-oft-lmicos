<style>
/* ═══════════════════════════════════════════════════
   HISTORIAL WIDGET
═══════════════════════════════════════════════════ */
.hw-wrap {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 4px rgba(15,23,42,.06);
    overflow: hidden;
    font-family: 'DM Sans', system-ui, sans-serif;
    margin-top: .25rem;
}
.dark .hw-wrap {
    background: #1e293b;
    border-color: rgba(51,65,85,.7);
}

/* ── Cabecera ── */
.hw-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.dark .hw-header { border-bottom-color: #334155; }

.hw-header-icon {
    width: 2.5rem; height: 2.5rem;
    border-radius: .75rem;
    background: linear-gradient(135deg,#6366f1,#818cf8);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.hw-header-title {
    font-size: .9375rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.dark .hw-header-title { color: #f1f5f9; }
.hw-header-sub {
    font-size: .75rem;
    color: #94a3b8;
    margin-top: .1rem;
}

.hw-chips {
    margin-left: auto;
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}
.hw-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .3rem .75rem; border-radius: 999px;
    font-size: .72rem; font-weight: 700;
    letter-spacing: .04em;
}
.hw-chip-atend { background: #d1fae5; color: #065f46; }
.dark .hw-chip-atend { background: rgba(52,211,153,.15); color: #6ee7b7; }
.hw-chip-cancel { background: #fee2e2; color: #991b1b; }
.dark .hw-chip-cancel { background: rgba(248,113,113,.15); color: #fca5a5; }

/* ── Grid de cards ── */
.hw-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr));
    gap: 1rem;
    padding: 1.25rem 1.5rem 1.5rem;
}
@media (max-width: 640px) { .hw-grid { grid-template-columns: 1fr; } }

/* ── Card individual ── */
.hw-card {
    border-radius: .875rem;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    overflow: hidden;
    transition: box-shadow .15s, transform .15s;
    display: flex;
    flex-direction: column;
}
.hw-card:hover {
    box-shadow: 0 4px 16px rgba(15,23,42,.08);
    transform: translateY(-2px);
}
.dark .hw-card { background: #0f172a; border-color: #334155; }
.dark .hw-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.3); }

.hw-card-stripe {
    height: 3px;
    width: 100%;
    flex-shrink: 0;
}
.hw-card-stripe-atend  { background: linear-gradient(90deg,#10b981,#34d399); }
.hw-card-stripe-cancel { background: linear-gradient(90deg,#ef4444,#f87171); }

.hw-card-body { padding: .875rem 1rem; flex: 1; display: flex; flex-direction: column; gap: .625rem; }

/* fecha + badge */
.hw-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .5rem;
}
.hw-card-date-block { display: flex; flex-direction: column; }
.hw-card-day {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -.04em;
    color: #0f172a;
}
.dark .hw-card-day { color: #f1f5f9; }
.hw-card-month {
    font-size: .7rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-top: .1rem;
}
.dark .hw-card-month { color: #94a3b8; }

.hw-badge {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .25rem .625rem; border-radius: 999px;
    font-size: .62rem; font-weight: 700;
    letter-spacing: .04em; text-transform: uppercase;
    white-space: nowrap; flex-shrink: 0;
}
.hw-badge-atend  { background: #d1fae5; color: #065f46; }
.dark .hw-badge-atend  { background: rgba(52,211,153,.15); color: #6ee7b7; }
.hw-badge-cancel { background: #fee2e2; color: #991b1b; }
.dark .hw-badge-cancel { background: rgba(248,113,113,.15); color: #fca5a5; }

/* hora + duración */
.hw-card-time {
    display: flex; align-items: center; gap: .375rem;
    font-size: .78rem; font-weight: 500; color: #475569;
}
.dark .hw-card-time { color: #94a3b8; }
.hw-card-time-dot {
    width: .25rem; height: .25rem; border-radius: 50%;
    background: #cbd5e1; flex-shrink: 0;
}

/* observaciones */
.hw-card-obs {
    margin-top: auto;
    padding: .625rem .75rem;
    background: #fff;
    border-radius: .625rem;
    border: 1px solid #e2e8f0;
    font-size: .78rem;
    color: #374151;
    line-height: 1.55;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.dark .hw-card-obs { background: #1e293b; border-color: #334155; color: #cbd5e1; }
.hw-card-no-obs {
    margin-top: auto;
    font-size: .75rem;
    color: #cbd5e1;
    font-style: italic;
    padding-top: .25rem;
}
.dark .hw-card-no-obs { color: #475569; }

/* ── Estado vacío ── */
.hw-empty {
    padding: 3rem 1.5rem;
    text-align: center;
}
.hw-empty-icon {
    width: 3.5rem; height: 3.5rem;
    border-radius: 1rem;
    background: #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
}
.dark .hw-empty-icon { background: #0f172a; }
.hw-empty-title {
    font-size: .9rem; font-weight: 600;
    color: #475569; margin-bottom: .375rem;
}
.dark .hw-empty-title { color: #94a3b8; }
.hw-empty-sub { font-size: .8rem; color: #94a3b8; }
.dark .hw-empty-sub { color: #64748b; }
</style>

<div class="hw-wrap">

    {{-- Cabecera --}}
    <div class="hw-header">
        <div class="hw-header-icon">
            <svg style="width:1.25rem;height:1.25rem;color:#fff" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/>
            </svg>
        </div>
        <div>
            <div class="hw-header-title">Historial de citas</div>
            <div class="hw-header-sub">
                {{ $totalAtendidas + $totalCanceladas }} sesiones registradas
            </div>
        </div>
        <div class="hw-chips">
            @if($totalAtendidas > 0)
            <span class="hw-chip hw-chip-atend">
                <svg style="width:.7rem;height:.7rem" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
                {{ $totalAtendidas }} atendida{{ $totalAtendidas !== 1 ? 's' : '' }}
            </span>
            @endif
            @if($totalCanceladas > 0)
            <span class="hw-chip hw-chip-cancel">
                <svg style="width:.7rem;height:.7rem" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
                {{ $totalCanceladas }} cancelada{{ $totalCanceladas !== 1 ? 's' : '' }}
            </span>
            @endif
        </div>
    </div>

    {{-- Contenido --}}
    @if($citas->isEmpty())
    <div class="hw-empty">
        <div class="hw-empty-icon">
            <svg style="width:1.5rem;height:1.5rem;color:#94a3b8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
            </svg>
        </div>
        <div class="hw-empty-title">Sin historial de citas</div>
        <div class="hw-empty-sub">Las citas atendidas o canceladas aparecerán aquí.</div>
    </div>

    @else
    <div class="hw-grid">
        @foreach($citas as $cita)
        <div class="hw-card">
            <div class="hw-card-stripe hw-card-stripe-{{ $cita->estado }}"></div>
            <div class="hw-card-body">

                {{-- Fecha + Badge --}}
                <div class="hw-card-top">
                    <div class="hw-card-date-block">
                        <span class="hw-card-day">{{ $cita->inicio->format('d') }}</span>
                        <span class="hw-card-month">{{ $cita->inicio->isoFormat('MMM YYYY') }}</span>
                    </div>
                    <span class="hw-badge hw-badge-{{ $cita->estado }}">
                        @if($cita->estado === 'atendida')
                            <svg style="width:.6rem;height:.6rem" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            Atendida
                        @else
                            <svg style="width:.6rem;height:.6rem" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                            Cancelada
                        @endif
                    </span>
                </div>

                {{-- Hora y duración --}}
                <div class="hw-card-time">
                    <svg style="width:.8rem;height:.8rem;flex-shrink:0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    {{ $cita->inicio->format('H:i') }}
                    <span class="hw-card-time-dot"></span>
                    {{ $cita->duracion_minutos }} min
                </div>

                {{-- Observaciones --}}
                @if($cita->observaciones)
                    <div class="hw-card-obs">{{ $cita->observaciones }}</div>
                @else
                    <div class="hw-card-no-obs">Sin observaciones registradas</div>
                @endif

            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
