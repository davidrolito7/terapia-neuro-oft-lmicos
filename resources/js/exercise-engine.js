/**
 * Pure vanilla-JS exercise animation engine.
 * Ported from useExerciseEngine.js (Vue composable).
 *
 * @param {() => object} getConfig - Returns current config object
 * @param {(state: string, elapsed: number) => void} onStateChange - Called on state or elapsed change
 */
export function createExerciseEngine(getConfig, onStateChange) {
    let state = 'idle'; // idle | countdown | running | paused | stopped
    let elapsedSeconds = 0;
    let countdownValue = 0;
    let canvasEl = null;

    let rafId = null;
    let lastTimestamp = null;
    let elapsedTime = 0;
    let countdownTimer = null;

    const saccade = { initialized: false, currentPos: { x: 0, y: 0 }, nextPos: { x: 0, y: 0 }, holdRemaining: 0 };
    const zigzag = { x: 0, y: 0, vx: 0, vy: 0, initialized: false, minX: 0, maxX: 0, minY: 0, maxY: 0 };
    const PARTICLE_COUNT = 5;
    const particlesState = { initialized: false, items: [] };

    function cfg() { return getConfig(); }
    function canvas() { return canvasEl; }
    function ctx() { return canvasEl?.getContext('2d') ?? null; }
    function speedFactor() { return 0.3 + ((cfg().speed - 1) * 1.5) / 9; }
    function margin() { return cfg().size + 8; }

    function notify() { onStateChange?.(state, elapsedSeconds); }

    function setState(s) { state = s; notify(); }

    function extents(w, h) {
        const m = margin();
        return { cx: w / 2, cy: h / 2, ex: w / 2 - m, ey: h / 2 - m, r: Math.min(w / 2, h / 2) - m };
    }

    function randomPos(w, h) {
        const m = margin();
        return { x: m + Math.random() * (w - m * 2), y: m + Math.random() * (h - m * 2) };
    }

    // ─── Path computation ─────────────────────────────────────────────────────

    function walkPath(v, t, sf) {
        const n = v.length, TAU = Math.PI * 2;
        const cycle = (t * sf) % TAU, segLen = TAU / n;
        const side = Math.min(Math.floor(cycle / segLen), n - 1), frac = (cycle % segLen) / segLen;
        const a = v[side], b = v[(side + 1) % n];
        return { x: a.x + (b.x - a.x) * frac, y: a.y + (b.y - a.y) * frac };
    }

    function regularPoly(n, cx, cy, r) {
        return Array.from({ length: n }, (_, i) => ({
            x: cx + r * Math.cos(-Math.PI / 2 + Math.PI * 2 * i / n),
            y: cy + r * Math.sin(-Math.PI / 2 + Math.PI * 2 * i / n)
        }));
    }

    function computePosition(t) {
        const c = canvas();
        if (!c) return { x: 0, y: 0 };
        const w = c.width, h = c.height;
        const sf = speedFactor();
        const { cx, cy, ex, ey, r } = extents(w, h);
        if (r <= 0) return { x: cx, y: cy };

        switch (cfg().exerciseType) {
            case 'circular': return { x: cx + r * Math.cos(t * sf), y: cy + r * Math.sin(t * sf) };
            case 'circular_ccw': return { x: cx + r * Math.cos(-t * sf), y: cy + r * Math.sin(-t * sf) };
            case 'figure8': return { x: cx + ex * Math.sin(2 * t * sf), y: cy + ey * Math.sin(t * sf) };
            case 'figure8_ccw': return { x: cx + ex * Math.sin(-2 * t * sf), y: cy + ey * Math.sin(-t * sf) };
            case 'figure8_v': return { x: cx + ex * Math.sin(t * sf), y: cy + ey * Math.sin(2 * t * sf) };
            case 'vertical': return { x: cx, y: cy + ey * Math.sin(t * sf) };
            case 'vertical_rev': return { x: cx, y: cy - ey * Math.sin(t * sf) };
            case 'horizontal': return { x: cx + ex * Math.cos(t * sf), y: cy };
            case 'diagonal': { const p = Math.sin(t * sf); return { x: cx + ex * p, y: cy + ey * p }; }
            case 'diagonal_tr': { const p = Math.sin(t * sf); return { x: cx + ex * p, y: cy - ey * p }; }
            case 'triangular': {
                const v = [{ x: cx, y: cy - r }, { x: cx + r * 0.866, y: cy + r * 0.5 }, { x: cx - r * 0.866, y: cy + r * 0.5 }];
                const cycle = (t * sf) % (Math.PI * 2), segLen = (Math.PI * 2) / 3;
                const side = Math.min(Math.floor(cycle / segLen), 2), frac = (cycle % segLen) / segLen;
                const a = v[side], b = v[(side + 1) % 3];
                return { x: a.x + (b.x - a.x) * frac, y: a.y + (b.y - a.y) * frac };
            }
            case 'square': {
                const corners = [{ x: cx - ex, y: cy - ey }, { x: cx + ex, y: cy - ey }, { x: cx + ex, y: cy + ey }, { x: cx - ex, y: cy + ey }];
                const cycle = (t * sf) % (Math.PI * 2), segLen = (Math.PI * 2) / 4;
                const side = Math.min(Math.floor(cycle / segLen), 3), frac = (cycle % segLen) / segLen;
                const a = corners[side], b = corners[(side + 1) % 4];
                return { x: a.x + (b.x - a.x) * frac, y: a.y + (b.y - a.y) * frac };
            }
            case 'spiral': {
                const pf = sf * 0.25;
                const s = Math.abs(Math.asin(Math.sin(t * pf)) / (Math.PI / 2));
                const p = 0.09524, q = 0.90476;
                const rNorm = (-p + Math.sqrt(p * p + 4 * q * s)) / (2 * q);
                const rad = r * 0.05 + r * 0.95 * rNorm;
                const angle = rNorm * Math.PI * 6;
                return { x: cx + rad * Math.cos(angle), y: cy + rad * Math.sin(angle) };
            }
            case 'zigzag': return handleZigzagPosition(w, h);
            case 'saccade': return handleSaccadePosition(w, h);
            case 'bee_h': {
                const loops = 5;
                const TAU = Math.PI * 2;
                const tNorm = (t * sf) % (TAU * 2);
                const p = tNorm <= TAU ? tNorm : TAU * 2 - tNorm;
                const progress = p / TAU;
                const centerX = cx - ex + progress * ex * 2;
                const r = ey * 0.35;
                return {
                    x: centerX + Math.cos(p * loops) * r,
                    y: cy + Math.sin(p * loops) * r
                };
            }

            case 'bee_v': {
                const loops = 5;
                const TAU = Math.PI * 2;
                const tNorm = (t * sf) % (TAU * 2);
                const p = tNorm <= TAU ? tNorm : TAU * 2 - tNorm;
                const progress = p / TAU;
                const centerY = cy - ey + progress * ey * 2;
                const r = ex * 0.35;
                return {
                    x: cx + Math.sin(p * loops) * r,
                    y: centerY + Math.cos(p * loops) * r
                };
            }
            case 'wave_h': {
                const tri = Math.asin(Math.sin(t * sf)) / (Math.PI / 2);
                const arch = Math.cos(tri * Math.PI / 2);
                return { x: cx + ex * tri, y: cy + ey * 0.88 * (1 - 2 * arch) };
            }
            case 'wave_h_inv': {
                const tri = Math.asin(Math.sin(t * sf)) / (Math.PI / 2);
                const arch = Math.cos(tri * Math.PI / 2);
                return { x: cx + ex * tri, y: cy - ey * 0.88 * (1 - 2 * arch) };
            }
            case 'spring': {
                const travel = Math.cos(t * sf * 0.55), bounce = Math.sin(t * sf * 5.5);
                return { x: cx + ex * travel, y: cy + ey * bounce };
            }
            case 'particles': {
                if (!particlesState.initialized) initParticles(w, h);
                const first = particlesState.items[0];
                return first ? { x: first.x, y: first.y } : { x: cx, y: cy };
            }
            case 'pentagon': return walkPath(regularPoly(5, cx, cy, r), t, sf);
            case 'hexagon': return walkPath(regularPoly(6, cx, cy, r), t, sf);
            case 'arrow_bi': {
                const aw = ex * 0.3,
                    bh = ey * 0.32;
                return walkPath([{
                    x: cx - ex,
                    y: cy
                },
                {
                    x: cx - ex + aw,
                    y: cy - ey
                },
                {
                    x: cx - ex + aw,
                    y: cy - bh
                },
                {
                    x: cx + ex - aw,
                    y: cy - bh
                },
                {
                    x: cx + ex - aw,
                    y: cy - ey
                },
                {
                    x: cx + ex,
                    y: cy
                },
                {
                    x: cx + ex - aw,
                    y: cy + ey
                },
                {
                    x: cx + ex - aw,
                    y: cy + bh
                },
                {
                    x: cx - ex + aw,
                    y: cy + bh
                },
                {
                    x: cx - ex + aw,
                    y: cy + ey
                },
                ], t, sf);
            }
            case 'cruz': {
                const tw = ex * 0.3,
                    th = ey * 0.3;
                return walkPath([{
                    x: cx - tw,
                    y: cy - ey
                },
                {
                    x: cx + tw,
                    y: cy - ey
                },
                {
                    x: cx + tw,
                    y: cy - th
                },
                {
                    x: cx + ex,
                    y: cy - th
                },
                {
                    x: cx + ex,
                    y: cy + th
                },
                {
                    x: cx + tw,
                    y: cy + th
                },
                {
                    x: cx + tw,
                    y: cy + ey
                },
                {
                    x: cx - tw,
                    y: cy + ey
                },
                {
                    x: cx - tw,
                    y: cy + th
                },
                {
                    x: cx - ex,
                    y: cy + th
                },
                {
                    x: cx - ex,
                    y: cy - th
                },
                {
                    x: cx - tw,
                    y: cy - th
                },
                ], t, sf);
            }
            case 'equis': {
                const w = Math.min(ex, ey) * 0.28;

                return walkPath([{
                    x: cx - ex,
                    y: cy - ey + w
                },
                {
                    x: cx - ex + w,
                    y: cy - ey
                },

                {
                    x: cx,
                    y: cy - w
                },
                {
                    x: cx + ex - w,
                    y: cy - ey
                },

                {
                    x: cx + ex,
                    y: cy - ey + w
                },
                {
                    x: cx + w,
                    y: cy
                },

                {
                    x: cx + ex,
                    y: cy + ey - w
                },
                {
                    x: cx + ex - w,
                    y: cy + ey
                },

                {
                    x: cx,
                    y: cy + w
                },
                {
                    x: cx - ex + w,
                    y: cy + ey
                },

                {
                    x: cx - ex,
                    y: cy + ey - w
                },
                {
                    x: cx - w,
                    y: cy
                },

                ], t, sf);
            }
            default: return { x: cx, y: cy };
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
        if (cfg().exerciseType !== 'saccade' || !saccade.initialized) return;
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
        const m = margin(), speed = speedFactor() * 280;
        zigzag.x = m; zigzag.y = h / 2;
        zigzag.vx = speed; zigzag.vy = speed * 0.7;
        zigzag.minX = m; zigzag.maxX = w - m;
        zigzag.minY = m; zigzag.maxY = h - m;
        zigzag.initialized = true;
    }

    function handleZigzagPosition(w, h) {
        if (!zigzag.initialized) initZigzag(w, h);
        return { x: zigzag.x, y: zigzag.y };
    }

    function updateZigzag(dt, w, h) {
        if (cfg().exerciseType !== 'zigzag') return;
        if (!zigzag.initialized) initZigzag(w, h);
        const m = margin();
        zigzag.maxX = w - m; zigzag.maxY = h - m;
        zigzag.x += zigzag.vx * dt; zigzag.y += zigzag.vy * dt;
        if (zigzag.x <= zigzag.minX) { zigzag.x = zigzag.minX; zigzag.vx = Math.abs(zigzag.vx); }
        if (zigzag.x >= zigzag.maxX) { zigzag.x = zigzag.maxX; zigzag.vx = -Math.abs(zigzag.vx); }
        if (zigzag.y <= zigzag.minY) { zigzag.y = zigzag.minY; zigzag.vy = Math.abs(zigzag.vy); }
        if (zigzag.y >= zigzag.maxY) { zigzag.y = zigzag.maxY; zigzag.vy = -Math.abs(zigzag.vy); }
    }

    // ─── Particles ────────────────────────────────────────────────────────────

    function initParticles(w, h) {
        const m = margin(), speed = speedFactor() * 220;
        particlesState.items = Array.from({ length: PARTICLE_COUNT }, () => {
            const angle = Math.random() * Math.PI * 2, spd = speed * (0.5 + Math.random() * 0.8);
            return { x: m + Math.random() * (w - m * 2), y: m + Math.random() * (h - m * 2), vx: Math.cos(angle) * spd, vy: Math.sin(angle) * spd };
        });
        particlesState.initialized = true;
    }

    function updateParticles(dt, w, h) {
        if (cfg().exerciseType !== 'particles') return;
        if (!particlesState.initialized) initParticles(w, h);
        const m = margin();
        for (const p of particlesState.items) {
            p.x += p.vx * dt; p.y += p.vy * dt;
            if (p.x <= m) { p.x = m; p.vx = Math.abs(p.vx); }
            if (p.x >= w - m) { p.x = w - m; p.vx = -Math.abs(p.vx); }
            if (p.y <= m) { p.y = m; p.vy = Math.abs(p.vy); }
            if (p.y >= h - m) { p.y = h - m; p.vy = -Math.abs(p.vy); }
        }
    }

    // ─── Rendering ────────────────────────────────────────────────────────────

    function clearCanvas(color = '#0f172a') {
        const c = canvas(), g = ctx();
        if (!c || !g) return;
        g.fillStyle = color;
        g.fillRect(0, 0, c.width, c.height);
    }

    function drawStimulus(g, x, y) {
        const size = cfg().size, color = cfg().color;
        g.save();

        const grd = g.createRadialGradient(x, y, 0, x, y, size * 2.5);
        grd.addColorStop(0, color + '55');
        grd.addColorStop(1, 'transparent');
        g.beginPath();
        g.arc(x, y, size * 2.5, 0, Math.PI * 2);
        g.fillStyle = grd;
        g.fill();

        switch (cfg().stimulusType) {
            case 'dot':
                g.beginPath(); g.arc(x, y, size, 0, Math.PI * 2);
                g.fillStyle = color; g.fill(); break;
            case 'ring':
                g.beginPath(); g.arc(x, y, size, 0, Math.PI * 2);
                g.strokeStyle = color; g.lineWidth = Math.max(2, size * 0.25); g.stroke(); break;
            case 'star': {
                g.beginPath();
                for (let i = 0; i < 10; i++) {
                    const angle = (i * Math.PI) / 5 - Math.PI / 2;
                    const radius = i % 2 === 0 ? size : size * 0.4;
                    const px = x + radius * Math.cos(angle), py = y + radius * Math.sin(angle);
                    if (i === 0) g.moveTo(px, py); else g.lineTo(px, py);
                }
                g.closePath(); g.fillStyle = color; g.fill(); break;
            }
            case 'cross': {
                const t = Math.max(2, size * 0.28);
                g.fillStyle = color;
                g.fillRect(x - size, y - t, size * 2, t * 2);
                g.fillRect(x - t, y - size, t * 2, size * 2); break;
            }
            case 'emoji':
                g.font = `${size * 2.2}px Arial, sans-serif`;
                g.textAlign = 'center'; g.textBaseline = 'middle';
                g.fillText(cfg().emoji, x, y); break;
        }
        g.restore();
    }

    function drawGhostPath(g) {
        const type = cfg().exerciseType;
        if (['saccade', 'zigzag', 'particles'].includes(type)) return;

        const sf = speedFactor();
        const isBee = ['bee_h', 'bee_v'].includes(type);
        const isSpiral = type === 'spiral';
        const period = isBee ?
            (Math.PI * 4) / Math.max(0.01, sf) :
            isSpiral ?
            (Math.PI * 2) / Math.max(0.01, sf * 0.25) :
            (Math.PI * 2) / Math.max(0.01, sf);
        const steps = isBee ? 800 : isSpiral ? 600 : 120;

        g.save();
        g.beginPath();
        for (let i = 0; i <= steps; i++) {
            const p = computePosition((i / steps) * period);
            i === 0 ? g.moveTo(p.x, p.y) : g.lineTo(p.x, p.y);
        }
        g.strokeStyle = cfg().color + '28';
        g.lineWidth = 1.5;
        g.stroke();
        g.restore();
    }

    function drawCountdownOnCanvas(value) {
        const c = canvas(), g = ctx();
        if (!c || !g) return;
        clearCanvas();
        const fontSize = Math.min(c.width, c.height) * 0.28;
        g.save();
        g.font = `bold ${fontSize}px Inter, system-ui, sans-serif`;
        g.textAlign = 'center'; g.textBaseline = 'middle';
        g.fillStyle = '#22d3ee'; g.globalAlpha = 0.9;
        g.fillText(String(value), c.width / 2, c.height / 2);
        g.font = `${fontSize * 0.18}px Inter, system-ui, sans-serif`;
        g.fillStyle = '#94a3b8'; g.globalAlpha = 0.7;
        g.fillText('Prepárese…', c.width / 2, c.height / 2 + fontSize * 0.6);
        g.restore();
    }

    function renderFrame() {
        const c = canvas(), g = ctx();
        if (!c || !g) return;
        clearCanvas();
        drawGhostPath(g);
        if (cfg().exerciseType === 'particles') {
            if (!particlesState.initialized) initParticles(c.width, c.height);
            for (const p of particlesState.items) drawStimulus(g, p.x, p.y);
        } else {
            const pos = computePosition(elapsedTime);
            drawStimulus(g, pos.x, pos.y);
        }
    }

    // ─── Animation loop ───────────────────────────────────────────────────────

    function tick(timestamp) {
        if (state !== 'running') { rafId = null; return; }
        if (lastTimestamp !== null) {
            const dt = Math.min((timestamp - lastTimestamp) / 1000, 0.1);
            elapsedTime += dt;
            const newElapsed = Math.floor(elapsedTime);
            if (newElapsed !== elapsedSeconds) { elapsedSeconds = newElapsed; notify(); }

            const c = canvas();
            if (c) { updateSaccade(dt); updateZigzag(dt, c.width, c.height); updateParticles(dt, c.width, c.height); }

            if (cfg().duration > 0 && elapsedTime >= cfg().duration) { stop(); return; }
        }
        lastTimestamp = timestamp;
        renderFrame();
        rafId = requestAnimationFrame(tick);
    }

    // ─── Public controls ─────────────────────────────────────────────────────

    function start() {
        if (state !== 'idle' && state !== 'stopped') return;
        elapsedTime = 0; elapsedSeconds = 0; lastTimestamp = null;
        saccade.initialized = false; zigzag.initialized = false;
        particlesState.initialized = false; particlesState.items = [];
        const delay = cfg().delay ?? 0;
        if (delay > 0) {
            countdownValue = delay;
            setState('countdown');
            drawCountdownOnCanvas(delay);
            countdownTimer = setInterval(() => {
                countdownValue--;
                drawCountdownOnCanvas(countdownValue);
                if (countdownValue <= 0) { clearInterval(countdownTimer); countdownTimer = null; beginRunning(); }
            }, 1000);
        } else { beginRunning(); }
    }

    function beginRunning() { setState('running'); rafId = requestAnimationFrame(tick); }

    function pause() { if (state !== 'running') return; setState('paused'); lastTimestamp = null; }
    function resume() { if (state !== 'paused') return; setState('running'); rafId = requestAnimationFrame(tick); }

    function stop() {
        if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
        setState('stopped');
        lastTimestamp = null;
        clearCanvas();
    }

    function reset() {
        stop();
        elapsedTime = 0; elapsedSeconds = 0; countdownValue = 0;
        saccade.initialized = false; zigzag.initialized = false;
        particlesState.initialized = false; particlesState.items = [];
        setState('idle');
        clearCanvas();
    }

    function redrawAfterResize() {
        if (state === 'running' || state === 'paused') renderFrame();
        else if (state === 'countdown') drawCountdownOnCanvas(countdownValue);
        else clearCanvas();
    }

    function setCanvas(el) { canvasEl = el; }

    return { setCanvas, start, pause, resume, stop, reset, redrawAfterResize };
}
