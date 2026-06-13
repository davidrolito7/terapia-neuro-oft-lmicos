<x-filament-panels::page>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">

<style>
/* ══════════════════════════════════════════════════════════
   LAYOUT — no depende de Tailwind
══════════════════════════════════════════════════════════ */
.cal-page {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    font-family: 'DM Sans', system-ui, sans-serif;
}
.cal-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .75rem;
}
.cal-cal-wrap {
    padding: 1.25rem;
}

/* ── Próximas citas (debajo del calendario) ───────────── */
.cal-proximas {
    padding: 1.25rem;
}
.cal-proximas-hd {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: 1rem;
}
.cal-proximas-hd-lbl {
    font-size: .8rem;
    font-weight: 700;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.dark .cal-proximas-hd-lbl { color: #e2e8f0; }
.cal-proximas-hd-sep {
    flex: 1;
    height: 1px;
    background: #e2e8f0;
}
.dark .cal-proximas-hd-sep { background: #334155; }

.cal-proximas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr));
    gap: .625rem;
}

.cal-prox-group-lbl {
    font-size: .6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #0891b2;
    margin-bottom: .3rem;
    padding: 0 .25rem;
}

.cita-card {
    display: flex;
    align-items: center;
    gap: .625rem;
    padding: .625rem .75rem;
    border-radius: .625rem;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    text-decoration: none;
    color: inherit;
    transition: background .12s, border-color .12s, transform .12s;
}
.cita-card:hover {
    background: #fff;
    border-color: #0891b2;
    transform: translateY(-1px);
}
.dark .cita-card { background: #0f172a; border-color: #334155; }
.dark .cita-card:hover { background: #1e293b; border-color: #0891b2; }
.cita-card-time {
    font-size: .7rem;
    font-weight: 700;
    color: #0891b2;
    flex-shrink: 0;
    min-width: 2.5rem;
}
.cita-card-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: .75rem;
    font-weight: 500;
    color: #1e293b;
}
.dark .cita-card-name { color: #cbd5e1; }

/* ══════════════════════════════════════════════════════════
   PANEL (fondo/borde compartido)
══════════════════════════════════════════════════════════ */
.cal-panel {
    background: #ffffff;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 4px rgba(15,23,42,.06);
    overflow: hidden;
    color: #0f172a;
}
.dark .cal-panel {
    background: #1e293b;
    border-color: rgba(51,65,85,.7);
    color: #f1f5f9;
}

/* ══════════════════════════════════════════════════════════
   STAT CARDS
══════════════════════════════════════════════════════════ */
.cal-stat {
    display: flex;
    align-items: center;
    gap: .875rem;
    padding: 1rem 1.25rem;
    position: relative;
    overflow: hidden;
    animation: fadeUp .3s ease both;
}
.cal-stat:nth-child(2) { animation-delay: .07s; }
.cal-stat:nth-child(3) { animation-delay: .14s; }

.cal-stat-icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.cal-stat-body { flex: 1; min-width: 0; }
.cal-stat-num {
    font-size: 1.875rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -.03em;
}
.cal-stat-label {
    font-size: .68rem;
    font-weight: 600;
    color: #64748b;
    margin-top: .25rem;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.dark .cal-stat-label { color: #94a3b8; }

.cal-stat-num-cyan    { color: #0891b2; }
.cal-stat-num-indigo  { color: #6366f1; }
.cal-stat-num-emerald { color: #10b981; }
.dark .cal-stat-num-cyan    { color: #22d3ee; }
.dark .cal-stat-num-indigo  { color: #a5b4fc; }
.dark .cal-stat-num-emerald { color: #34d399; }

/* Mobile: 3 columnas compactas */
@media (max-width: 540px) {
    .cal-stats { gap: .4rem; }
    .cal-stat { flex-direction: column; align-items: center; text-align: center; gap: .5rem; padding: .875rem .5rem; }
    .cal-stat-icon { width: 2rem; height: 2rem; border-radius: .5rem; }
    .cal-stat-icon .ic-md { width: 1rem !important; height: 1rem !important; }
    .cal-stat-num { font-size: 1.375rem; }
    .cal-stat-label { font-size: .6rem; margin-top: .125rem; }
}

/* ══════════════════════════════════════════════════════════
   SIDEBAR
══════════════════════════════════════════════════════════ */
.cita-dot {
    width: .625rem;
    height: .625rem;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ══════════════════════════════════════════════════════════
   FULLCALENDAR
══════════════════════════════════════════════════════════ */
.fc { font-family: 'DM Sans', system-ui, sans-serif !important; }
.fc .fc-toolbar { gap: 10px; flex-wrap: wrap; }
.fc .fc-toolbar-title {
    font-size: 1.05rem !important;
    font-weight: 600 !important;
    color: #0f172a;
    letter-spacing: -.01em;
}
.dark .fc .fc-toolbar-title { color: #e2e8f0; }

.fc-button-primary {
    background: #0891b2 !important;
    border-color: #0891b2 !important;
    border-radius: 7px !important;
    font-family: 'DM Sans', sans-serif !important;
    font-size: .75rem !important;
    font-weight: 500 !important;
    padding: 5px 11px !important;
    transition: all .15s ease !important;
}
.fc-button-primary:hover:not(:disabled) {
    background: #0e7490 !important;
    border-color: #0e7490 !important;
}
.fc-button-primary:disabled { opacity: .4 !important; }
.fc-button-primary.fc-button-active { background: #0e7490 !important; border-color: #0e7490 !important; }
.fc-button-group .fc-button-primary { border-radius: 0 !important; }
.fc-button-group .fc-button-primary:first-child { border-radius: 7px 0 0 7px !important; }
.fc-button-group .fc-button-primary:last-child  { border-radius: 0 7px 7px 0 !important; }

.fc-theme-standard td, .fc-theme-standard th { border-color: #e2e8f0 !important; }
.dark .fc-theme-standard td,
.dark .fc-theme-standard th { border-color: #334155 !important; }
.fc-theme-standard .fc-scrollgrid { border-color: #e2e8f0 !important; border-radius: 10px; overflow: hidden; }
.dark .fc-theme-standard .fc-scrollgrid { border-color: #334155 !important; }

.fc-col-header-cell { background: #f8fafc !important; padding: 7px 0 !important; }
.dark .fc-col-header-cell { background: #1e2a3a !important; }
.fc-col-header-cell-cushion {
    font-size: .67rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #94a3b8 !important;
    text-decoration: none !important;
}
.fc-daygrid-day-number {
    font-size: .78rem !important;
    font-weight: 500 !important;
    color: #0f172a !important;
    text-decoration: none !important;
    padding: 5px 7px !important;
}
.dark .fc-daygrid-day-number { color: #cbd5e1 !important; }
.fc-day-today { background: rgba(239,246,255,.8) !important; }
.dark .fc-day-today { background: rgba(8,145,178,.1) !important; }
.fc-day-today .fc-daygrid-day-number {
    background: #0891b2 !important;
    color: #fff !important;
    border-radius: 50%;
    width: 24px; height: 24px;
    display: flex !important;
    align-items: center; justify-content: center;
    margin: 4px; padding: 0 !important;
    font-weight: 700 !important;
}

.fc-event {
    cursor: pointer !important;
    border-radius: 5px !important;
    border: none !important;
    font-family: 'DM Sans', sans-serif !important;
    font-size: .7rem !important;
    font-weight: 500 !important;
    padding: 2px 5px !important;
    transition: filter .1s ease, transform .1s ease;
}
.fc-event:hover { filter: brightness(.88); transform: translateY(-1px); }
.fc-event.cancelada { opacity: .3 !important; text-decoration: line-through; }
.fc-event.atendida  { filter: saturate(.5) brightness(.95); }

.fc-timegrid-slot-label, .fc-timegrid-axis { color: #94a3b8 !important; font-size: .68rem !important; }
.fc-timegrid-now-indicator-line { border-color: #ef4444 !important; border-width: 2px !important; }
.fc-timegrid-now-indicator-arrow { border-top-color: #ef4444 !important; }
.fc-list-day-cushion { background: #f1f5f9 !important; }
.dark .fc-list-day-cushion { background: #1e2a3a !important; }
.fc-list-day-text, .fc-list-day-side-text {
    font-size: .75rem !important; font-weight: 600 !important;
    color: #0891b2 !important; text-decoration: none !important;
}
.fc-list-event:hover td { background: rgba(8,145,178,.04) !important; }
.dark .fc-list-event:hover td { background: #1e2a3a !important; }
.fc-popover {
    background: #fff !important; border-color: #e2e8f0 !important;
    border-radius: 10px !important; box-shadow: 0 8px 30px rgba(0,0,0,.12) !important;
}
.fc-popover-header {
    background: #f8fafc !important; border-radius: 10px 10px 0 0 !important;
    font-size: .75rem !important; font-weight: 600 !important;
}
.dark .fc-popover { background: #1e293b !important; border-color: #334155 !important; }
.dark .fc-popover-header { background: #0f172a !important; color: #e2e8f0 !important; }
.dark .fc { color: #cbd5e1; }
.dark .fc-list-empty { background: #1e293b; color: #94a3b8; }

/* ══════════════════════════════════════════════════════════
   MODAL
══════════════════════════════════════════════════════════ */
.cal-overlay {
    position: fixed; inset: 0; z-index: 50;
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
    font-family: 'DM Sans', system-ui, sans-serif;
}
.cal-overlay-bg {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.4);
    backdrop-filter: blur(3px);
}
.cal-modal {
    position: relative;
    background: #fff;
    border-radius: 1.25rem;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
    max-width: 420px;
    width: 100%;
    overflow: hidden;
    animation: fadeUp .2s ease both;
}
.dark .cal-modal { background: #0f172a; }

.cal-modal-hd { display:flex; align-items:flex-start; justify-content:space-between; padding:1.25rem 1.5rem 1rem; }
.cal-modal-title { font-size:.9375rem; font-weight:600; color:#0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dark .cal-modal-title { color:#f1f5f9; }
.cal-modal-meta  { font-size:.8rem; color:#64748b; margin-top:.2rem; padding-left:.875rem; }
.dark .cal-modal-meta { color:#94a3b8; }

.cal-badge {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: .625rem; font-weight: 700;
    padding: 2px 8px; border-radius: 999px;
    letter-spacing: .04em; text-transform: uppercase;
    white-space: nowrap;
}
.badge-pending  { background:#fef3c7; color:#92400e; }
.badge-attended { background:#d1fae5; color:#065f46; }
.badge-canceled { background:#fee2e2; color:#991b1b; }
.badge-recur    { background:#e0f2fe; color:#075985; }
.dark .badge-pending  { background:rgba(251,191,36,.15); color:#fcd34d; }
.dark .badge-attended { background:rgba(52,211,153,.15); color:#6ee7b7; }
.dark .badge-canceled { background:rgba(248,113,113,.15); color:#fca5a5; }
.dark .badge-recur    { background:rgba(56,189,248,.15);  color:#7dd3fc; }

.cal-modal-notes {
    margin: 0 1.5rem .875rem;
    padding: .75rem;
    background: #f8fafc;
    border-radius: .75rem;
    border: 1px solid #e2e8f0;
}
.dark .cal-modal-notes { background: #1e293b; border-color: #334155; }
.cal-modal-notes-prev { border-color: #e0f2fe; background: #f0f9ff; }
.dark .cal-modal-notes-prev { background: rgba(3,105,161,.12); border-color: rgba(56,189,248,.25); }
.cal-modal-notes-prev .cal-modal-notes-lbl { color: #0369a1; }
.dark .cal-modal-notes-prev .cal-modal-notes-lbl { color: #7dd3fc; }
.cal-modal-notes-prev .cal-modal-notes-text { color: #0c4a6e; }
.dark .cal-modal-notes-prev .cal-modal-notes-text { color: #bae6fd; }
.cal-modal-notes-lbl {
    font-size: .6rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .1em; color: #94a3b8; margin-bottom: .3rem;
}
.cal-modal-notes-text { font-size: .8rem; color: #374151; line-height: 1.5; }
.dark .cal-modal-notes-text { color: #d1d5db; }

.cal-modal-obs-wrap { margin: 0 1.5rem .875rem; }
.cal-modal-obs-lbl  {
    display: block; font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: #64748b; margin-bottom: .4rem;
}
.cal-modal-textarea {
    width: 100%; border-radius: .75rem;
    border: 1px solid #e2e8f0; background: #fff;
    color: #111827; font-size: .8rem;
    padding: .625rem .75rem; resize: none;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    transition: border-color .15s;
}
.dark .cal-modal-textarea { background: #1e293b; border-color: #334155; color: #f1f5f9; }
.cal-modal-textarea:focus { border-color: #0891b2; }

.cal-modal-actions {
    display: flex; align-items: center; gap: .5rem;
    padding: .75rem 1.5rem 1.25rem;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.dark .cal-modal-actions { border-color: #1e293b; }

.btn-primary {
    flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
    padding: .5rem 1rem; border-radius: .625rem;
    font-family: 'DM Sans', sans-serif; font-size: .8rem; font-weight: 600;
    color: #fff; border: none; cursor: pointer;
    transition: opacity .15s, transform .1s;
}
.btn-primary:hover { opacity: .88; transform: translateY(-1px); }
.btn-danger {
    display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
    padding: .5rem .875rem; border-radius: .625rem;
    font-family: 'DM Sans', sans-serif; font-size: .8rem; font-weight: 600;
    background: #fef2f2; color: #dc2626; border: none; cursor: pointer;
    transition: background .15s;
}
.btn-danger:hover { background: #fee2e2; }
.dark .btn-danger { background: rgba(239,68,68,.12); color: #f87171; }
.dark .btn-danger:hover { background: rgba(239,68,68,.22); }
.btn-ghost {
    display: inline-flex; align-items: center; justify-content: center;
    padding: .5rem .75rem; border-radius: .625rem;
    font-family: 'DM Sans', sans-serif; font-size: .8rem; font-weight: 500;
    background: transparent; color: #64748b; border: none; cursor: pointer;
    transition: background .15s, color .15s;
}
.btn-ghost:hover { background: #f1f5f9; color: #0f172a; }
.dark .btn-ghost:hover { background: #1e293b; color: #e2e8f0; }
.btn-edit {
    margin-left: auto; display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem .875rem; border-radius: .625rem;
    font-family: 'DM Sans', sans-serif; font-size: .8rem; font-weight: 600;
    background: #f1f5f9; color: #374151; text-decoration: none;
    transition: background .15s;
}
.btn-edit:hover { background: #e2e8f0; }
.dark .btn-edit { background: #1e293b; color: #cbd5e1; }
.dark .btn-edit:hover { background: #334155; }

.btn-close {
    display: flex; align-items: center; justify-content: center;
    width: 1.75rem; height: 1.75rem; border-radius: .5rem;
    background: transparent; border: none; cursor: pointer;
    color: #94a3b8; transition: background .12s, color .12s;
}
.btn-close:hover { background: #f1f5f9; color: #374151; }
.dark .btn-close:hover { background: #1e293b; color: #e2e8f0; }

/* ══ SELECTOR DE ESTADO ══ */
.cal-status-pills {
    display: flex; gap: .375rem;
    padding: 0 1.5rem 1rem;
}
.cal-status-pill {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: .3rem;
    padding: .45rem .5rem; border-radius: .625rem;
    font-size: .65rem; font-weight: 700;
    letter-spacing: .04em; text-transform: uppercase;
    cursor: pointer; border: 2px solid transparent;
    transition: all .15s; opacity: .4; white-space: nowrap;
    font-family: 'DM Sans', sans-serif;
}
.cal-status-pill.is-active { opacity: 1; border-color: currentColor; }
.cal-status-pill:not(.is-active):hover { opacity: .7; }
.pill-pending  { background:#fef3c7; color:#92400e; }
.pill-attended { background:#d1fae5; color:#065f46; }
.pill-canceled { background:#fee2e2; color:#991b1b; }
.dark .pill-pending  { background:rgba(251,191,36,.15); color:#fcd34d; }
.dark .pill-attended { background:rgba(52,211,153,.15); color:#6ee7b7; }
.dark .pill-canceled { background:rgba(248,113,113,.15); color:#fca5a5; }

/* ══════════════════════════════════════════════════════════
   ICON SIZES — immune a overrides de Filament
══════════════════════════════════════════════════════════ */
.ic-xs { width:.75rem !important; height:.75rem !important; display:block; flex-shrink:0; }
.ic-sm { width:1rem !important;   height:1rem !important;   display:block; flex-shrink:0; }
.ic-md { width:1.5rem !important; height:1.5rem !important; display:block; flex-shrink:0; }
.ic-lg { width:2.5rem !important; height:2.5rem !important; display:block; flex-shrink:0; }

/* ══════════════════════════════════════════════════════════
   ANIMACIONES
══════════════════════════════════════════════════════════ */
@keyframes fadeUp {
    from { opacity:0; transform:translateY(8px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>

{{-- ══════════════════════════════════════════════════════
     PÁGINA
══════════════════════════════════════════════════════ --}}
<div class="cal-page">

    {{-- Stat cards --}}
    <div class="cal-stats">

        <div class="cal-panel cal-stat">
            <div class="cal-stat-icon" style="background:linear-gradient(135deg,#0891b2,#06b6d4)">
                <svg class="ic-md" style="color:#fff" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
            <div class="cal-stat-body">
                <div class="cal-stat-num cal-stat-num-cyan">{{ $this->getCitasHoy() }}</div>
                <div class="cal-stat-label">Citas hoy</div>
            </div>
        </div>

        <div class="cal-panel cal-stat">
            <div class="cal-stat-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">
                <svg class="ic-md" style="color:#fff" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"/>
                </svg>
            </div>
            <div class="cal-stat-body">
                <div class="cal-stat-num cal-stat-num-indigo">{{ $this->getCitasSemana() }}</div>
                <div class="cal-stat-label">Esta semana</div>
            </div>
        </div>

        <div class="cal-panel cal-stat">
            <div class="cal-stat-icon" style="background:linear-gradient(135deg,#10b981,#34d399)">
                <svg class="ic-md" style="color:#fff" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
            <div class="cal-stat-body">
                <div class="cal-stat-num cal-stat-num-emerald">{{ $this->getCitasMes() }}</div>
                <div class="cal-stat-label">Este mes</div>
            </div>
        </div>

    </div>

    {{-- Calendario (ancho completo) --}}
    <div class="cal-panel cal-cal-wrap"
         x-data="citasCalendar(@js($this->getCalendarEvents()))"
         x-init="init()"
         wire:ignore>
        <div id="calendario-citas" style="min-height:600px"></div>
    </div>

    {{-- Próximas citas (debajo, ancho completo) --}}
    @php
        $proximas = $this->getProximasCitas();
        $grupos   = [];
        foreach ($proximas as $c) {
            if ($c['hoy'])        $grupos['Hoy'][]    = $c;
            elseif ($c['manana']) $grupos['Mañana'][] = $c;
            else                  $grupos[$c['fecha']][] = $c;
        }
    @endphp

    @if(count($proximas) > 0)
    <div class="cal-panel cal-proximas">

        <div class="cal-proximas-hd">
            <svg class="ic-sm" style="color:#0891b2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
            </svg>
            <span class="cal-proximas-hd-lbl">Próximas citas</span>
            <div class="cal-proximas-hd-sep"></div>
        </div>

        @foreach ($grupos as $titulo => $citas)
            <div style="margin-bottom:.875rem">
                <div class="cal-prox-group-lbl">{{ $titulo }}</div>
                <div class="cal-proximas-grid">
                    @foreach ($citas as $cita)
                        <a href="{{ $cita['edit_url'] }}" class="cita-card">
                            <span class="cita-dot" style="background:{{ $cita['color'] }}"></span>
                            <span class="cita-card-time">{{ $cita['hora'] }}</span>
                            <span class="cita-card-name">{{ $cita['paciente'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

    </div>
    @endif

</div>

{{-- ══════════════════════════════════════════════════════
     MODAL DE DETALLE
══════════════════════════════════════════════════════ --}}
@if($mostrarDetalle && $citaSeleccionada)
<div class="cal-overlay">
    <div class="cal-overlay-bg" wire:click="cerrarDetalle"></div>

    <div class="cal-modal" style="border-top:3px solid {{ $citaSeleccionada['color'] }}">

        {{-- Header --}}
        <div class="cal-modal-hd">
            <div style="flex:1;min-width:0;padding-right:1rem">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem">
                    <span class="cita-dot" style="background:{{ $citaSeleccionada['color'] }}"></span>
                    <span class="cal-modal-title">{{ $citaSeleccionada['paciente'] }}</span>
                    @if($citaSeleccionada['recurrente'])
                        <span class="cal-badge badge-recur" style="flex-shrink:0">↻</span>
                    @endif
                </div>
                <div class="cal-modal-meta">
                    {{ $citaSeleccionada['inicio'] }} · {{ $citaSeleccionada['hora'] }} · {{ $citaSeleccionada['duracion'] }} min
                </div>
            </div>
            <button wire:click="cerrarDetalle" class="btn-close" style="flex-shrink:0">
                <svg class="ic-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Selector de estado --}}
        <div class="cal-status-pills">
            <button wire:click="$set('estadoInput','pendiente')"
                    class="cal-status-pill pill-pending {{ $estadoInput === 'pendiente' ? 'is-active' : '' }}">
                <svg class="ic-xs" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Pendiente
            </button>
            <button wire:click="$set('estadoInput','atendida')"
                    class="cal-status-pill pill-attended {{ $estadoInput === 'atendida' ? 'is-active' : '' }}">
                <svg class="ic-xs" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Atendida
            </button>
            <button wire:click="$set('estadoInput','cancelada')"
                    class="cal-status-pill pill-canceled {{ $estadoInput === 'cancelada' ? 'is-active' : '' }}">
                <svg class="ic-xs" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Cancelada
            </button>
        </div>

        {{-- Sesión anterior --}}
        @if(!empty($citaSeleccionada['cita_anterior_obs']))
        <div class="cal-modal-notes cal-modal-notes-prev">
            <div class="cal-modal-notes-lbl">Sesión anterior · {{ $citaSeleccionada['cita_anterior_fecha'] }}</div>
            <div class="cal-modal-notes-text">{{ $citaSeleccionada['cita_anterior_obs'] }}</div>
        </div>
        @endif

        {{-- Observaciones de esta sesión --}}
        <div class="cal-modal-obs-wrap">
            <label class="cal-modal-obs-lbl">Observaciones de esta sesión</label>
            <textarea wire:model="observacionesInput"
                      rows="4"
                      class="cal-modal-textarea"
                      placeholder="Describe los ejercicios realizados, progreso del paciente…"></textarea>
        </div>

        {{-- Acciones --}}
        <div class="cal-modal-actions">
            <button wire:click="guardarCita"
                    wire:loading.attr="disabled"
                    class="btn-primary"
                    style="background:{{ $estadoInput === 'atendida' ? 'linear-gradient(135deg,#10b981,#34d399)' : ($estadoInput === 'cancelada' ? 'linear-gradient(135deg,#ef4444,#f87171)' : 'linear-gradient(135deg,#0891b2,#22d3ee)') }}">
                <svg wire:loading.remove wire:target="guardarCita" class="ic-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
                <svg wire:loading wire:target="guardarCita" class="ic-sm" style="animation:spin 1s linear infinite" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
                <span wire:loading.remove wire:target="guardarCita">Guardar cambios</span>
                <span wire:loading wire:target="guardarCita">Guardando…</span>
            </button>
            <a href="{{ $citaSeleccionada['edit_url'] }}" class="btn-edit">
                <svg class="ic-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                </svg>
                Editar
            </a>
        </div>

    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     FULLCALENDAR JS
══════════════════════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
function citasCalendar(eventos) {
    return {
        cal: null,
        eventos: eventos,
        init() {
            const wire = this.$wire;

            this.$nextTick(() => {
                this.cal = new FullCalendar.Calendar(
                    document.getElementById('calendario-citas'),
                    {
                        locale: 'es',
                        firstDay: 1,
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left:   'prev,next today',
                            center: 'title',
                            right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                        },
                        buttonText: { today:'Hoy', month:'Mes', week:'Semana', day:'Día', list:'Agenda' },
                        nowIndicator: true,
                        dayMaxEvents: 4,
                        height: 'auto',
                        events: this.eventos,
                        eventTimeFormat:  { hour:'2-digit', minute:'2-digit', meridiem:false, hour12:false },
                        slotLabelFormat:  { hour:'2-digit', minute:'2-digit', meridiem:false, hour12:false },
                        slotMinTime: '07:00:00',
                        slotMaxTime: '21:00:00',

                        eventDidMount: (info) => {
                            const estado = info.event.extendedProps.estado;
                            if (estado === 'cancelada') info.el.classList.add('cancelada');
                            if (estado === 'atendida')  info.el.classList.add('atendida');

                            const dur = info.event.extendedProps.duracion;
                            const obs = info.event.extendedProps.observaciones;
                            info.el.setAttribute('title',
                                `${info.event.title} · ${dur} min` + (obs ? `\n${obs.substring(0,80)}…` : '')
                            );

                            const bg = info.event.backgroundColor;
                            if (bg && bg.startsWith('#')) {
                                const r = parseInt(bg.slice(1,3),16),
                                      g = parseInt(bg.slice(3,5),16),
                                      b = parseInt(bg.slice(5,7),16);
                                info.el.style.color = (0.299*r + 0.587*g + 0.114*b)/255 > .55
                                    ? '#0f172a' : '#ffffff';
                            }
                        },

                        eventClick: (info) => {
                            wire.seleccionarCita(parseInt(info.event.id));
                        },
                    }
                );
                this.cal.render();
            });

            window.addEventListener('actualizar-calendario', (e) => {
                if (!this.cal) return;
                this.cal.removeAllEvents();
                (e.detail?.eventos ?? []).forEach(ev => this.cal.addEvent(ev));
            });
        }
    }
}
</script>

</x-filament-panels::page>
