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

    function randomPos(w, h) {
        const margin = Math.max(60, config.size + 20);
        return {
            x: margin + Math.random() * (w - margin * 2),
            y: margin + Math.random() * (h - margin * 2),
        };
    }

    // ─── Path computation ─────────────────────────────────────────────────────

    function computePosition(t) {
        const c = canvas();
        if (!c) return { x: 0, y: 0 };

        const w = c.width;
        const h = c.height;
        const sf = speedFactor();
        const cx = w / 2;
        const cy = h / 2;
        const margin = Math.max(60, config.size + 20);
        const r = Math.min(w, h) / 2 - margin;

        if (r <= 0) return { x: cx, y: cy };

        switch (config.exerciseType) {
            case 'circular':
                return {
                    x: cx + r * Math.cos(t * sf),
                    y: cy + r * Math.sin(t * sf),
                };

            case 'figure8': {
                return {
                    x: cx + r * Math.sin(2 * t * sf),
                    y: cy + r * 0.5 * Math.sin(t * sf),
                };
            }

            case 'vertical':
                return {
                    x: cx,
                    y: cy + r * Math.sin(t * sf),
                };

            case 'horizontal':
                return {
                    x: cx + r * Math.cos(t * sf),
                    y: cy,
                };

            case 'diagonal': {
                const phase = Math.sin(t * sf);
                return {
                    x: cx + r * 0.707 * phase,
                    y: cy + r * 0.707 * phase,
                };
            }

            case 'triangular': {
                // Equilateral triangle, CCW
                const v = [
                    { x: cx, y: cy - r },
                    { x: cx + r * 0.866, y: cy + r * 0.5 },
                    { x: cx - r * 0.866, y: cy + r * 0.5 },
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

            case 'saccade':
                return handleSaccadePosition(w, h);

            default:
                return { x: cx, y: cy };
        }
    }

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

        // Hint text
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

        const pos = computePosition(elapsedTime);
        drawStimulus(g, pos.x, pos.y);
    }

    // ─── Animation loop ───────────────────────────────────────────────────────

    function tick(timestamp) {
        if (state.value !== 'running') {
            rafId = null;
            return;
        }

        if (lastTimestamp !== null) {
            // Cap dt to 100ms to handle tab switching / focus loss
            const dt = Math.min((timestamp - lastTimestamp) / 1000, 0.1);
            elapsedTime += dt;
            elapsedSeconds.value = Math.floor(elapsedTime);

            updateSaccade(dt);

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
        // RAF will stop itself on next tick
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
        // RAF stops itself; draw idle canvas
        clearCanvas();
    }

    function reset() {
        stop();
        elapsedTime = 0;
        elapsedSeconds.value = 0;
        countdownValue.value = 0;
        saccade.initialized = false;
        state.value = 'idle';
        clearCanvas();
    }

    // Re-draw current frame after canvas resize (keeps image valid when paused)
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
