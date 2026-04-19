import { onUnmounted, ref } from 'vue';

export function useExerciseEngine(config) {
    // Public state
    const state = ref('idle'); // idle | countdown | running | paused | stopped
    const countdownValue = ref(0);
    const elapsedSeconds = ref(0);
    const canvasRef = ref(null);

    // Internal animation state
    let rafId = null;
    let lastTimestamp = null;
    let elapsedTime = 0;
    let countdownTimer = null;

    // Saccade exercise internal state
    const saccade = {
        initialized: false,
        currentPos: { x: 0, y: 0 },
        nextPos: { x: 0, y: 0 },
        holdRemaining: 0,
    };

    // Zigzag exercise internal state
    const zigzag = {
        x: 0,
        y: 0,
        vx: 0,
        vy: 0,
        initialized: false,
    };

    // Particles exercise internal state
    const PARTICLE_COUNT = 5;
    const particlesState = {
        initialized: false,
        items: [], // { x, y, vx, vy }
    };

    // ─── Helpers ─────────────────────────────────────────────────────────────

    function canvas() {
        return canvasRef.value;
    }

    function ctx() {
        return canvasRef.value?.getContext('2d') ?? null;
    }

    function speedFactor() {
        // Maps slider 1-10 → 0.3 to 1.8 (rad/s or traversals/s)
        return 0.3 + ((config.speed - 1) * 1.5) / 9;
    }

    function margin() {
        return config.size + 8;
    }

    function extents(w, h) {
        const m = margin();
        return {
            cx: w / 2,
            cy: h / 2,
            ex: w / 2 - m,   // full horizontal half-extent
            ey: h / 2 - m,   // full vertical half-extent
            r:  Math.min(w / 2, h / 2) - m, // radius for circular paths
        };
    }

    function randomPos(w, h) {
        const m = margin();
        return {
            x: m + Math.random() * (w - m * 2),
            y: m + Math.random() * (h - m * 2),
        };
    }

    // ─── Path computation ─────────────────────────────────────────────────────

    function computePosition(t) {
        const c = canvas();
        if (!c) return { x: 0, y: 0 };

        const w = c.width;
        const h = c.height;
        const sf = speedFactor();
        const { cx, cy, ex, ey, r } = extents(w, h);

        if (r <= 0) return { x: cx, y: cy };

        switch (config.exerciseType) {

            // ── Circular (clockwise) ───────────────────────────────────────────
            case 'circular':
                return {
                    x: cx + r * Math.cos(t * sf),
                    y: cy + r * Math.sin(t * sf),
                };

            // ── Circular counter-clockwise ─────────────────────────────────────
            case 'circular_ccw':
                return {
                    x: cx + r * Math.cos(-t * sf),
                    y: cy + r * Math.sin(-t * sf),
                };

            // ── Figure-8 (horizontal) ──────────────────────────────────────────
            case 'figure8':
                return {
                    x: cx + ex * Math.sin(2 * t * sf),
                    y: cy + ey * Math.sin(t * sf),
                };

            // ── Figure-8 reversed ─────────────────────────────────────────────
            case 'figure8_ccw':
                return {
                    x: cx + ex * Math.sin(-2 * t * sf),
                    y: cy + ey * Math.sin(-t * sf),
                };

            // ── Figure-8 vertical (Lissajous 1:2) ─────────────────────────────
            case 'figure8_v':
                return {
                    x: cx + ex * Math.sin(t * sf),
                    y: cy + ey * Math.sin(2 * t * sf),
                };

            // ── Vertical ──────────────────────────────────────────────────────
            case 'vertical':
                return {
                    x: cx,
                    y: cy + ey * Math.sin(t * sf),
                };

            // ── Vertical (bottom to top) ───────────────────────────────────────
            case 'vertical_rev':
                return {
                    x: cx,
                    y: cy - ey * Math.sin(t * sf),
                };

            // ── Horizontal ────────────────────────────────────────────────────
            case 'horizontal':
                return {
                    x: cx + ex * Math.cos(t * sf),
                    y: cy,
                };

            // ── Diagonal top-left ↔ bottom-right ──────────────────────────────
            case 'diagonal': {
                const phase = Math.sin(t * sf);
                return {
                    x: cx + ex * phase,
                    y: cy + ey * phase,
                };
            }

            // ── Diagonal top-right ↔ bottom-left ──────────────────────────────
            case 'diagonal_tr': {
                const phase = Math.sin(t * sf);
                return {
                    x: cx + ex * phase,
                    y: cy - ey * phase,
                };
            }

            // ── Triangular ────────────────────────────────────────────────────
            case 'triangular': {
                const v = [
                    { x: cx,              y: cy - r },
                    { x: cx + r * 0.866,  y: cy + r * 0.5 },
                    { x: cx - r * 0.866,  y: cy + r * 0.5 },
                ];
                const cycle = (t * sf) % (Math.PI * 2);
                const segLen = (Math.PI * 2) / 3;
                const side = Math.min(Math.floor(cycle / segLen), 2);
                const frac = (cycle % segLen) / segLen;
                const a = v[side];
                const b = v[(side + 1) % 3];
                return {
                    x: a.x + (b.x - a.x) * frac,
                    y: a.y + (b.y - a.y) * frac,
                };
            }

            // ── Square/Rectangle ──────────────────────────────────────────────
            case 'square': {
                // Four corners traversed linearly
                const corners = [
                    { x: cx - ex, y: cy - ey },
                    { x: cx + ex, y: cy - ey },
                    { x: cx + ex, y: cy + ey },
                    { x: cx - ex, y: cy + ey },
                ];
                const cycle = (t * sf) % (Math.PI * 2);
                const segLen = (Math.PI * 2) / 4;
                const side = Math.min(Math.floor(cycle / segLen), 3);
                const frac = (cycle % segLen) / segLen;
                const a = corners[side];
                const b = corners[(side + 1) % 4];
                return {
                    x: a.x + (b.x - a.x) * frac,
                    y: a.y + (b.y - a.y) * frac,
                };
            }

            // ── Spiral (retrocede por la misma línea) ─────────────────────────
            case 'spiral': {
                const pf = sf * 0.25;
                // s = onda triangular 0→1→0 (parámetro de tiempo lineal)
                const s  = Math.abs(Math.asin(Math.sin(t * pf)) / (Math.PI / 2));
                // Corrección de velocidad: parameterización por longitud de arco.
                // Para a=0.05r, b=0.95r: p=a/(a+b/2), q=(b/2)/(a+b/2)
                // Invertir s = p·rNorm + q·rNorm²  →  rNorm = (-p + √(p²+4q·s)) / (2q)
                const p = 0.09524, q = 0.90476;
                const rNorm = (-p + Math.sqrt(p * p + 4 * q * s)) / (2 * q);
                const rad   = r * 0.05 + r * 0.95 * rNorm;
                // Ángulo = función de rNorm (no de t) → al regresar traza la misma curva
                const angle = rNorm * Math.PI * 6;
                return {
                    x: cx + rad * Math.cos(angle),
                    y: cy + rad * Math.sin(angle),
                };
            }

            // ── Zigzag (billiard bounce) ───────────────────────────────────────
            case 'zigzag':
                return handleZigzagPosition(w, h);

            // ── Saccade (random jumps) ─────────────────────────────────────────
            case 'saccade':
                return handleSaccadePosition(w, h);

            // ── Bee horizontal ────────────────────────────────────────────────
            case 'bee_h': {
                const N     = 3;
                const loopR = ex / (N + 1);
                const fSlow = sf * 0.3;
                const fFast = fSlow * N * 2;
                const tri   = Math.asin(Math.sin(t * fSlow)) / (Math.PI / 2);
                const cX    = cx + (ex - loopR) * tri;
                return {
                    x: cX + loopR * Math.cos(t * fFast),
                    y: cy  + loopR * Math.sin(t * fFast),
                };
            }

            // ── Bee vertical ──────────────────────────────────────────────────
            case 'bee_v': {
                const N     = 3;
                const loopR = ey / (N + 1);
                const fSlow = sf * 0.3;
                const fFast = fSlow * N * 2;
                const tri   = Math.asin(Math.sin(t * fSlow)) / (Math.PI / 2);
                const cY    = cy + (ey - loopR) * tri;
                return {
                    x: cx  + loopR * Math.cos(t * fFast),
                    y: cY  + loopR * Math.sin(t * fFast),
                };
            }

            // ── Arco de onda ∩  (inicio/fin en la parte BAJA, pico arriba) ────
            case 'wave_h': {
                const tri  = Math.asin(Math.sin(t * sf)) / (Math.PI / 2);
                const arch = Math.cos(tri * Math.PI / 2); // 1 en centro, 0 en extremos
                // extremos → cy+ey*0.88 (abajo), centro → cy-ey*0.88 (arriba)
                return {
                    x: cx + ex * tri,
                    y: cy + ey * 0.88 * (1 - 2 * arch),
                };
            }

            // ── Arco de onda ∪  (inicio/fin arriba, fondo abajo) ─────────────
            case 'wave_h_inv': {
                const tri  = Math.asin(Math.sin(t * sf)) / (Math.PI / 2);
                const arch = Math.cos(tri * Math.PI / 2);
                return {
                    x: cx + ex * tri,
                    y: cy - ey * 0.88 * (1 - 2 * arch),
                };
            }

            // ── Spring / gusanito (alcanza extremos ±ey) ──────────────────────
            case 'spring': {
                const travel = Math.cos(t * sf * 0.55);
                const bounce = Math.sin(t * sf * 5.5);
                return {
                    x: cx + ex * travel,
                    y: cy + ey * bounce,   // amplitud completa
                };
            }

            // ── Particles (puntos aleatorios) ─────────────────────────────────
            // El render especial se maneja en renderFrame(); aquí devolvemos
            // la posición del primer punto como fallback.
            case 'particles': {
                if (!particlesState.initialized) initParticles(w, h);
                const first = particlesState.items[0];
                return first ? { x: first.x, y: first.y } : { x: cx, y: cy };
            }

            default:
                return { x: cx, y: cy };
        }
    }

    // ─── Saccade ──────────────────────────────────────────────────────────────

    function handleSaccadePosition(w, h) {
        if (!saccade.initialized) {
            saccade.currentPos = randomPos(w, h);
            saccade.nextPos = randomPos(w, h);
            saccade.holdRemaining = 2.5 / speedFactor();
            saccade.initialized = true;
        }
        return saccade.currentPos;
    }

    function updateSaccade(dt) {
        if (config.exerciseType !== 'saccade' || !saccade.initialized) return;
        saccade.holdRemaining -= dt;
        if (saccade.holdRemaining <= 0) {
            saccade.currentPos = { ...saccade.nextPos };
            const c = canvas();
            if (c) saccade.nextPos = randomPos(c.width, c.height);
            saccade.holdRemaining = 2.5 / speedFactor();
        }
    }

    // ─── Zigzag ───────────────────────────────────────────────────────────────

    function initZigzag(w, h) {
        const m = margin();
        const speed = speedFactor() * 280; // pixels per second
        // Start at center-left with random-ish diagonal direction
        zigzag.x = m;
        zigzag.y = h / 2;
        zigzag.vx = speed;
        zigzag.vy = speed * 0.7;
        zigzag.initialized = true;
        zigzag.minX = m;
        zigzag.maxX = w - m;
        zigzag.minY = m;
        zigzag.maxY = h - m;
    }

    function handleZigzagPosition(w, h) {
        if (!zigzag.initialized) initZigzag(w, h);
        return { x: zigzag.x, y: zigzag.y };
    }

    function updateZigzag(dt, w, h) {
        if (config.exerciseType !== 'zigzag') return;
        if (!zigzag.initialized) initZigzag(w, h);

        const m = margin();
        zigzag.maxX = w - m;
        zigzag.maxY = h - m;

        zigzag.x += zigzag.vx * dt;
        zigzag.y += zigzag.vy * dt;

        if (zigzag.x <= zigzag.minX) { zigzag.x = zigzag.minX; zigzag.vx = Math.abs(zigzag.vx); }
        if (zigzag.x >= zigzag.maxX) { zigzag.x = zigzag.maxX; zigzag.vx = -Math.abs(zigzag.vx); }
        if (zigzag.y <= zigzag.minY) { zigzag.y = zigzag.minY; zigzag.vy = Math.abs(zigzag.vy); }
        if (zigzag.y >= zigzag.maxY) { zigzag.y = zigzag.maxY; zigzag.vy = -Math.abs(zigzag.vy); }
    }

    // ─── Particles ────────────────────────────────────────────────────────────

    function initParticles(w, h) {
        const m = margin();
        const speed = speedFactor() * 220;
        particlesState.items = Array.from({ length: PARTICLE_COUNT }, () => {
            const angle = Math.random() * Math.PI * 2;
            const spd   = speed * (0.5 + Math.random() * 0.8);
            return {
                x:  m + Math.random() * (w - m * 2),
                y:  m + Math.random() * (h - m * 2),
                vx: Math.cos(angle) * spd,
                vy: Math.sin(angle) * spd,
            };
        });
        particlesState.initialized = true;
    }

    function updateParticles(dt, w, h) {
        if (config.exerciseType !== 'particles') return;
        if (!particlesState.initialized) initParticles(w, h);

        const m = margin();
        for (const p of particlesState.items) {
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            if (p.x <= m)     { p.x = m;     p.vx =  Math.abs(p.vx); }
            if (p.x >= w - m) { p.x = w - m; p.vx = -Math.abs(p.vx); }
            if (p.y <= m)     { p.y = m;     p.vy =  Math.abs(p.vy); }
            if (p.y >= h - m) { p.y = h - m; p.vy = -Math.abs(p.vy); }
        }
    }

    // ─── Rendering ────────────────────────────────────────────────────────────

    function clearCanvas(color = '#0f172a') {
        const c = canvas();
        const g = ctx();
        if (!c || !g) return;
        g.fillStyle = color;
        g.fillRect(0, 0, c.width, c.height);
    }

    function drawStimulus(g, x, y) {
        const size = config.size;
        const color = config.color;

        g.save();

        switch (config.stimulusType) {
            case 'dot':
                g.beginPath();
                g.arc(x, y, size, 0, Math.PI * 2);
                g.fillStyle = color;
                g.fill();
                break;

            case 'ring':
                g.beginPath();
                g.arc(x, y, size, 0, Math.PI * 2);
                g.strokeStyle = color;
                g.lineWidth = Math.max(2, size * 0.25);
                g.stroke();
                break;

            case 'star': {
                g.beginPath();
                for (let i = 0; i < 10; i++) {
                    const angle = (i * Math.PI) / 5 - Math.PI / 2;
                    const radius = i % 2 === 0 ? size : size * 0.4;
                    const px = x + radius * Math.cos(angle);
                    const py = y + radius * Math.sin(angle);
                    if (i === 0) g.moveTo(px, py);
                    else g.lineTo(px, py);
                }
                g.closePath();
                g.fillStyle = color;
                g.fill();
                break;
            }

            case 'cross': {
                const t = Math.max(2, size * 0.28);
                g.fillStyle = color;
                g.fillRect(x - size, y - t, size * 2, t * 2);
                g.fillRect(x - t, y - size, t * 2, size * 2);
                break;
            }

            case 'emoji':
                g.font = `${size * 2.2}px Arial, sans-serif`;
                g.textAlign = 'center';
                g.textBaseline = 'middle';
                g.fillText(config.emoji, x, y);
                break;
        }

        g.restore();
    }

    function drawCountdownOnCanvas(value) {
        const c = canvas();
        const g = ctx();
        if (!c || !g) return;

        clearCanvas();

        const fontSize = Math.min(c.width, c.height) * 0.28;
        g.save();
        g.font = `bold ${fontSize}px Inter, system-ui, sans-serif`;
        g.textAlign = 'center';
        g.textBaseline = 'middle';
        g.fillStyle = '#22d3ee';
        g.globalAlpha = 0.9;
        g.fillText(String(value), c.width / 2, c.height / 2);

        g.font = `${fontSize * 0.18}px Inter, system-ui, sans-serif`;
        g.fillStyle = '#94a3b8';
        g.globalAlpha = 0.7;
        g.fillText('Prepárese…', c.width / 2, c.height / 2 + fontSize * 0.6);
        g.restore();
    }

    function renderFrame() {
        const c = canvas();
        const g = ctx();
        if (!c || !g) return;

        clearCanvas();

        if (config.exerciseType === 'particles') {
            if (!particlesState.initialized) initParticles(c.width, c.height);
            for (const p of particlesState.items) {
                drawStimulus(g, p.x, p.y);
            }
        } else {
            const pos = computePosition(elapsedTime);
            drawStimulus(g, pos.x, pos.y);
        }
    }

    // ─── Animation loop ───────────────────────────────────────────────────────

    function tick(timestamp) {
        if (state.value !== 'running') {
            rafId = null;
            return;
        }

        if (lastTimestamp !== null) {
            const dt = Math.min((timestamp - lastTimestamp) / 1000, 0.1);
            elapsedTime += dt;
            elapsedSeconds.value = Math.floor(elapsedTime);

            const c = canvas();
            if (c) {
                updateSaccade(dt);
                updateZigzag(dt, c.width, c.height);
                updateParticles(dt, c.width, c.height);
            }

            if (config.duration > 0 && elapsedTime >= config.duration) {
                stop();
                return;
            }
        }

        lastTimestamp = timestamp;
        renderFrame();

        rafId = requestAnimationFrame(tick);
    }

    // ─── Public controls ─────────────────────────────────────────────────────

    function start() {
        if (state.value !== 'idle' && state.value !== 'stopped') return;

        elapsedTime = 0;
        elapsedSeconds.value = 0;
        lastTimestamp = null;
        saccade.initialized = false;
        zigzag.initialized = false;
        particlesState.initialized = false;
        particlesState.items = [];

        const delay = config.delay;

        if (delay > 0) {
            state.value = 'countdown';
            countdownValue.value = delay;
            drawCountdownOnCanvas(delay);

            countdownTimer = setInterval(() => {
                countdownValue.value--;
                drawCountdownOnCanvas(countdownValue.value);

                if (countdownValue.value <= 0) {
                    clearInterval(countdownTimer);
                    countdownTimer = null;
                    _beginRunning();
                }
            }, 1000);
        } else {
            _beginRunning();
        }
    }

    function _beginRunning() {
        state.value = 'running';
        rafId = requestAnimationFrame(tick);
    }

    function pause() {
        if (state.value !== 'running') return;
        state.value = 'paused';
        lastTimestamp = null;
    }

    function resume() {
        if (state.value !== 'paused') return;
        state.value = 'running';
        rafId = requestAnimationFrame(tick);
    }

    function stop() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        state.value = 'stopped';
        lastTimestamp = null;
        clearCanvas();
    }

    function reset() {
        stop();
        elapsedTime = 0;
        elapsedSeconds.value = 0;
        countdownValue.value = 0;
        saccade.initialized = false;
        zigzag.initialized = false;
        particlesState.initialized = false;
        particlesState.items = [];
        state.value = 'idle';
        clearCanvas();
    }

    function redrawAfterResize() {
        if (state.value === 'running' || state.value === 'paused') {
            renderFrame();
        } else if (state.value === 'countdown') {
            drawCountdownOnCanvas(countdownValue.value);
        } else {
            clearCanvas();
        }
    }

    onUnmounted(() => {
        if (rafId) cancelAnimationFrame(rafId);
        if (countdownTimer) clearInterval(countdownTimer);
    });

    return {
        canvasRef,
        state,
        countdownValue,
        elapsedSeconds,
        start,
        pause,
        resume,
        stop,
        reset,
        redrawAfterResize,
    };
}
