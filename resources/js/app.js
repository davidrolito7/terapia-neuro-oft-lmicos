import '../css/app.css';

import Alpine from 'alpinejs';
import { createExerciseEngine } from './exercise-engine';

// ─── Exercise session Alpine component ───────────────────────────────────────

Alpine.data('exerciseSession', (planItems) => {
    let _engine = null;
    let _restTimer = null;

    const tipoNombres = {
        circular:          'Circular',
        circular_ccw:      'Circular inverso',
        horizontal:        'Horizontal',
        vertical:          'Vertical',
        vertical_rev:      'Vertical inverso',
        diagonal:          'Diagonal ↖↘',
        diagonal_tr:       'Diagonal ↗↙',
        triangular:        'Triangular',
        square:            'Cuadrado',
        figure8:           'Figura 8',
        figure8_ccw:       'Figura 8 inverso',
        figure8_v:         'Figura 8 horizontal',
        spiral:            'Espiral',
        zigzag:            'Zigzag',
        saccade:           'Sacádico',
        spring:            'Resorte',
        particles:         'Puntos aleatorios',
        bee_h:             'Abeja horizontal',
        bee_v:             'Abeja vertical',
        wave_h:            'Arco de onda',
        wave_h_inv:        'Arco de onda invertido',
        pentagon:          'Pentágono',
        hexagon:           'Hexágono',
        arrow_bi:          'Flecha bidireccional',
        cruz:              'Cruz (+)',
        equis:             'Equis (×)',
        star_path:         'Estrella',
        hourglass:         'Reloj de arena',
        circular_bounce:   'Círculo rebote',
        s_curve:           'Curva S',
        orbit_shapes:      'Órbita de figuras',
        random_numbers:    'Números aleatorios',
        dual_bounce:       'Dos figuras rebotando',
        four_pulse:        'Cuatro puntos (pulso)',
        zigzag_numbers:    'Zigzag con números',
    };

    return {
        planItems,
        tipoNombres,
        pageState:    'idle',
        currentIndex: 0,
        restSeconds:  0,
        calificacion:         null,
        observaciones:        '',
        ardioOjo:             null,
        masEjercicios:        null,
        siguioTodos:          null,
        ejercicioNoSiguio:    null,
        ordenObjetos:         '',
        cansancioVista:       null,
        engineState:  'idle',
        elapsed:      0,
        isFullscreen: false,
        isInverted:   false,

        get currentItem()  { return this.planItems[this.currentIndex] ?? null; },
        get nextItem()     { return this.planItems[this.currentIndex + 1] ?? null; },
        get total()        { return this.planItems.length; },
        get exerciseName() {
            const item = this.currentItem;
            if (!item) return 'Terapia Visual';
            return tipoNombres[item.tipo_ejercicio] ?? item.tipo_ejercicio;
        },
        get nextExerciseName() {
            const item = this.nextItem;
            if (!item) return '';
            return tipoNombres[item.tipo_ejercicio] ?? item.tipo_ejercicio;
        },
        get restTotal() {
            return this.currentItem?.descanso_segundos ?? 30;
        },
        get restDashoffset() {
            return 276.5 * (1 - this.restSeconds / (this.restTotal || 1));
        },
        get exerciseDuration() {
            return this.currentItem?.duracion ?? 0;
        },
        get timerProgress() {
            if (this.exerciseDuration <= 0) return 0;
            return Math.min(1, this.elapsed / this.exerciseDuration);
        },

        init() {
            const self = this;

            _engine = createExerciseEngine(
                () => self._buildConfig(),
                (newState, elapsedSec) => {
                    self.engineState = newState;
                    if (elapsedSec !== undefined) self.elapsed = elapsedSec;
                }
            );

            this.$watch('engineState', (newState) => {
                if (newState === 'stopped' && this.pageState === 'exercising') {
                    this._markComplete(this.currentItem);
                    this._beginRest();
                }
            });

            this.$nextTick(() => {
                const canvas = this.$refs.canvas;
                if (!canvas) return;

                const syncSize = () => {
                    canvas.width  = canvas.clientWidth;
                    canvas.height = canvas.clientHeight;
                    _engine.redrawAfterResize();
                };

                const ro = new ResizeObserver(syncSize);
                ro.observe(canvas);
                syncSize();

                _engine.setCanvas(canvas);
                this.startAt(0);
            });

            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
            });
        },

        _buildConfig() {
            const item = this.currentItem;
            if (!item) return { exerciseType: 'circular', stimulusType: 'dot', emoji: '👁️', speed: 5, size: 20, color: '#22d3ee', delay: 3, duration: 60 };
            return {
                exerciseType: item.tipo_ejercicio,
                stimulusType: item.tipo_estimulo  ?? 'dot',
                emoji:        item.emoji_estimulo ?? '👁️',
                speed:        item.velocidad,
                size:         item.tamano,
                color:        item.color,
                duration:     item.duracion,
                delay:        3,
            };
        },

        startAt(index) {
            if (index >= this.planItems.length) { this._mostrarCalificacion(); return; }
            this.currentIndex = index;
            this.elapsed      = 0;
            this.pageState    = 'exercising';
            _engine.reset();
            _engine.start();
        },

        _markComplete(item) {
            if (!item) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            fetch(`/ejercicios/${item.id}/completar`, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            }).catch(() => {});
        },

        _beginRest() {
            if (this.currentIndex + 1 >= this.planItems.length) { this._mostrarCalificacion(); return; }
            this.restSeconds = this.currentItem?.descanso_segundos ?? 30;
            this.pageState   = 'resting';
            _restTimer = setInterval(() => {
                this.restSeconds--;
                if (this.restSeconds <= 0) { this._clearRest(); this.startAt(this.currentIndex + 1); }
            }, 1000);
        },

        _clearRest() {
            if (_restTimer) { clearInterval(_restTimer); _restTimer = null; }
        },

        skipRest() { this._clearRest(); this.startAt(this.currentIndex + 1); },

        _mostrarCalificacion() { this._clearRest(); this.pageState = 'rating'; },

        formatTime(s) {
            if (s < 60) return `${s}s`;
            return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        },

        toggleInvert() {
            this.isInverted = !this.isInverted;
        },

        togglePause() {
            if (this.engineState === 'running') _engine.pause();
            else if (this.engineState === 'paused') _engine.resume();
        },

        enviarCalificacion() {
            const set = (id, val) => { document.getElementById(id).value = val ?? ''; };
            set('cal-input',              this.calificacion);
            set('obs-input',              this.observaciones);
            set('ardio-ojo-input',        this.ardioOjo       === null ? '' : (this.ardioOjo       ? '1' : '0'));
            set('mas-ejercicios-input',   this.masEjercicios  === null ? '' : (this.masEjercicios  ? '1' : '0'));
            set('siguio-todos-input',     this.siguioTodos    === null ? '' : (this.siguioTodos    ? '1' : '0'));
            set('ejercicio-no-siguio-input', this.ejercicioNoSiguio ?? '');
            set('orden-objetos-input',    this.ordenObjetos);
            set('cansancio-vista-input',  this.cansancioVista === null ? '' : this.cansancioVista);
            document.getElementById('calificacion-form').submit();
        },

        destroy() { this._clearRest(); },
    };
});

window.Alpine = Alpine;
Alpine.start();
