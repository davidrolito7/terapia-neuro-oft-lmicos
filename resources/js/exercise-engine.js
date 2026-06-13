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
    const PARTICLE_COUNT = 25;
    const particlesState = { initialized: false, items: [], lastSf: 0 };

    const ORBIT_SHAPES = ['dot', 'triangle', 'square', 'diamond', 'pentagon', 'hexagon', 'star', 'cross'];
    const orbitState = { initialized: false, pending: [], visible: [], holdRemaining: 0 };
    const NUMBER_COUNT = 29;
    const numbersState = { initialized: false, visible: [], holdRemaining: 0 };
    const dualBounceState = { initialized: false, a: { x: 0, y: 0, vx: 0, vy: 0 }, b: { x: 0, y: 0, vx: 0, vy: 0 } };
    const ZN_ZIGS = 5;
    const zigzagNumState = { initialized: false, direction: 1, progress: 0, ball: { x: 0, y: 0 }, numbers: [], numTimer: 0 };

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

    // Igual que walkPath pero distribuye el tiempo proporcional a la longitud de cada segmento
    function walkPathArc(v, t, sf) {
        const n = v.length, TAU = Math.PI * 2;
        const lens = Array.from({ length: n }, (_, i) => {
            const a = v[i], b = v[(i + 1) % n];
            return Math.hypot(b.x - a.x, b.y - a.y);
        });
        const total = lens.reduce((s, l) => s + l, 0);
        const cum = [];
        let acc = 0;
        for (const l of lens) { acc += l / total; cum.push(acc); }
        const progress = ((t * sf) % TAU) / TAU;
        let seg = 0;
        while (seg < n - 1 && cum[seg] <= progress) seg++;
        const segStart = seg === 0 ? 0 : cum[seg - 1];
        const frac = (progress - segStart) / (cum[seg] - segStart);
        const a = v[seg], b = v[(seg + 1) % n];
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
                const rr = ey * 0.35;
                return { x: centerX + Math.cos(p * loops) * rr, y: cy + Math.sin(p * loops) * rr };
            }
            case 'bee_v': {
                const loops = 5;
                const TAU = Math.PI * 2;
                const tNorm = (t * sf) % (TAU * 2);
                const p = tNorm <= TAU ? tNorm : TAU * 2 - tNorm;
                const progress = p / TAU;
                const centerY = cy - ey + progress * ey * 2;
                const rr = ex * 0.35;
                return { x: cx + Math.sin(p * loops) * rr, y: centerY + Math.cos(p * loops) * rr };
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
                const aw = ex * 0.3, bh = ey * 0.32;
                return walkPath([
                    { x: cx - ex, y: cy },
                    { x: cx - ex + aw, y: cy - ey },
                    { x: cx - ex + aw, y: cy - bh },
                    { x: cx + ex - aw, y: cy - bh },
                    { x: cx + ex - aw, y: cy - ey },
                    { x: cx + ex, y: cy },
                    { x: cx + ex - aw, y: cy + ey },
                    { x: cx + ex - aw, y: cy + bh },
                    { x: cx - ex + aw, y: cy + bh },
                    { x: cx - ex + aw, y: cy + ey },
                ], t, sf);
            }
            case 'cruz': {
                const tw = ex * 0.3, th = ey * 0.3;
                return walkPath([
                    { x: cx - tw, y: cy - ey },
                    { x: cx + tw, y: cy - ey },
                    { x: cx + tw, y: cy - th },
                    { x: cx + ex, y: cy - th },
                    { x: cx + ex, y: cy + th },
                    { x: cx + tw, y: cy + th },
                    { x: cx + tw, y: cy + ey },
                    { x: cx - tw, y: cy + ey },
                    { x: cx - tw, y: cy + th },
                    { x: cx - ex, y: cy + th },
                    { x: cx - ex, y: cy - th },
                    { x: cx - tw, y: cy - th },
                ], t, sf);
            }
            case 'equis': {
                const ew = Math.min(ex, ey) * 0.28;
                return walkPath([
                    { x: cx - ex,      y: cy - ey + ew },
                    { x: cx - ex + ew, y: cy - ey      },
                    { x: cx,           y: cy - ew       },
                    { x: cx + ex - ew, y: cy - ey      },
                    { x: cx + ex,      y: cy - ey + ew },
                    { x: cx + ew,      y: cy            },
                    { x: cx + ex,      y: cy + ey - ew },
                    { x: cx + ex - ew, y: cy + ey      },
                    { x: cx,           y: cy + ew       },
                    { x: cx - ex + ew, y: cy + ey      },
                    { x: cx - ex,      y: cy + ey - ew },
                    { x: cx - ew,      y: cy            },
                ], t, sf);
            }
            case 'star_path': {
                const outerR = r, innerR = r * 0.4;
                const pts = Array.from({ length: 10 }, (_, i) => {
                    const angle = -Math.PI / 2 + (Math.PI * 2 * i) / 10;
                    const rad = i % 2 === 0 ? outerR : innerR;
                    return { x: cx + rad * Math.cos(angle), y: cy + rad * Math.sin(angle) };
                });
                return walkPath(pts, t, sf);
            }
            case 'hourglass': {
                return walkPathArc([
                    { x: cx - ex, y: cy - ey },
                    { x: cx + ex, y: cy - ey },
                    { x: cx,      y: cy      },
                    { x: cx + ex, y: cy + ey },
                    { x: cx - ex, y: cy + ey },
                    { x: cx,      y: cy      },
                ], t, sf);
            }
            case 'circular_bounce': {
                const TAU = Math.PI * 2;
                const tNorm = (t * sf) % (TAU * 2);
                const angle = tNorm <= TAU ? tNorm - Math.PI / 2 : (TAU * 2 - tNorm) - Math.PI / 2;
                return { x: cx + r * Math.cos(angle), y: cy + r * Math.sin(angle) };
            }
            case 's_curve': {
                const TAU = Math.PI * 2;
                const tNorm = (t * sf) % (TAU * 2);
                const tb = tNorm <= TAU ? tNorm : TAU * 2 - tNorm;
                if (tb <= Math.PI) {
                    const p = tb / Math.PI;
                    return { x: cx - ex + p * ex * 2, y: cy - ey * 0.7 * Math.sin(2 * tb) };
                }
                const p = (tb - Math.PI) / Math.PI;
                return { x: cx + ex - p * ex * 2, y: cy };
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
        const m = margin(), sf = speedFactor(), speed = sf * 220;
        particlesState.lastSf = sf;
        particlesState.items = Array.from({ length: PARTICLE_COUNT }, (_, i) => {
            const angle = Math.random() * Math.PI * 2;
            const spd = i === 0 ? speed * 1.5 : speed * (0.4 + Math.random() * 0.4);
            return { x: m + Math.random() * (w - m * 2), y: m + Math.random() * (h - m * 2), vx: Math.cos(angle) * spd, vy: Math.sin(angle) * spd };
        });
        particlesState.initialized = true;
    }

    function updateParticles(dt, w, h) {
        if (cfg().exerciseType !== 'particles') return;
        if (particlesState.initialized && speedFactor() !== particlesState.lastSf) {
            particlesState.initialized = false;
        }
        if (!particlesState.initialized) initParticles(w, h);
        const m = margin();
        for (let i = 0; i < particlesState.items.length; i++) {
            const p = particlesState.items[i];
            p.x += p.vx * dt; p.y += p.vy * dt;
            let bl = false, br = false, bt = false, bb = false;
            if (p.x <= m)     { p.x = m;     p.vx =  Math.abs(p.vx); bl = true; }
            if (p.x >= w - m) { p.x = w - m; p.vx = -Math.abs(p.vx); br = true; }
            if (p.y <= m)     { p.y = m;     p.vy =  Math.abs(p.vy); bt = true; }
            if (p.y >= h - m) { p.y = h - m; p.vy = -Math.abs(p.vy); bb = true; }
            if (i === 0 && (bl || br || bt || bb)) {
                const speed = Math.hypot(p.vx, p.vy);
                const angle = Math.atan2(p.vy, p.vx) + (Math.random() - 0.5) * Math.PI * 0.7;
                p.vx = Math.cos(angle) * speed;
                p.vy = Math.sin(angle) * speed;
                if (bl) p.vx =  Math.abs(p.vx);
                if (br) p.vx = -Math.abs(p.vx);
                if (bt) p.vy =  Math.abs(p.vy);
                if (bb) p.vy = -Math.abs(p.vy);
            }
        }
    }

    // ─── Orbit Shapes ─────────────────────────────────────────────────────────

    function initOrbit(w, h) {
        const { cx, cy, r } = extents(w, h);
        const shapes = [...ORBIT_SHAPES];
        for (let i = shapes.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shapes[i], shapes[j]] = [shapes[j], shapes[i]];
        }
        const positions = Array.from({ length: 8 }, (_, i) => {
            const baseAngle = Math.PI * 2 * i / 8;
            const angle = baseAngle + (Math.random() - 0.5) * (Math.PI / 4);
            const dist = r * (0.92 + Math.random() * 0.07);
            return { x: cx + dist * Math.cos(angle), y: cy + dist * Math.sin(angle), shape: shapes[i] };
        });
        for (let i = positions.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [positions[i], positions[j]] = [positions[j], positions[i]];
        }
        orbitState.pending = positions;
        orbitState.visible = [];
        orbitState.holdRemaining = 2.0 / speedFactor();
        orbitState.initialized = true;
    }

    function updateOrbit(dt, w, h) {
        if (cfg().exerciseType !== 'orbit_shapes') return;
        if (!orbitState.initialized) initOrbit(w, h);
        orbitState.holdRemaining -= dt;
        if (orbitState.holdRemaining <= 0) {
            if (orbitState.pending.length > 0) {
                orbitState.visible.push(orbitState.pending.shift());
            } else {
                initOrbit(w, h);
            }
            orbitState.holdRemaining = 2.0 / speedFactor();
        }
    }

    function drawOrbitShape(g, x, y, shape, size, color) {
        g.save();
        switch (shape) {
            case 'dot':
                g.beginPath(); g.arc(x, y, size, 0, Math.PI * 2);
                g.fillStyle = color; g.fill(); break;
            case 'ring':
                g.beginPath(); g.arc(x, y, size, 0, Math.PI * 2);
                g.strokeStyle = color; g.lineWidth = Math.max(2, size * 0.25); g.stroke(); break;
            case 'triangle':
                g.beginPath();
                for (let i = 0; i < 3; i++) {
                    const a = -Math.PI / 2 + Math.PI * 2 * i / 3;
                    i === 0 ? g.moveTo(x + size * Math.cos(a), y + size * Math.sin(a)) : g.lineTo(x + size * Math.cos(a), y + size * Math.sin(a));
                }
                g.closePath(); g.fillStyle = color; g.fill(); break;
            case 'square':
                g.fillStyle = color;
                g.fillRect(x - size * 0.8, y - size * 0.8, size * 1.6, size * 1.6); break;
            case 'diamond':
                g.beginPath(); g.moveTo(x, y - size); g.lineTo(x + size * 0.7, y); g.lineTo(x, y + size); g.lineTo(x - size * 0.7, y);
                g.closePath(); g.fillStyle = color; g.fill(); break;
            case 'pentagon':
                g.beginPath();
                for (let i = 0; i < 5; i++) {
                    const a = -Math.PI / 2 + Math.PI * 2 * i / 5;
                    i === 0 ? g.moveTo(x + size * Math.cos(a), y + size * Math.sin(a)) : g.lineTo(x + size * Math.cos(a), y + size * Math.sin(a));
                }
                g.closePath(); g.fillStyle = color; g.fill(); break;
            case 'hexagon':
                g.beginPath();
                for (let i = 0; i < 6; i++) {
                    const a = Math.PI / 6 + Math.PI * 2 * i / 6;
                    i === 0 ? g.moveTo(x + size * Math.cos(a), y + size * Math.sin(a)) : g.lineTo(x + size * Math.cos(a), y + size * Math.sin(a));
                }
                g.closePath(); g.fillStyle = color; g.fill(); break;
            case 'star':
                g.beginPath();
                for (let i = 0; i < 10; i++) {
                    const a = i * Math.PI / 5 - Math.PI / 2;
                    const rad = i % 2 === 0 ? size : size * 0.4;
                    i === 0 ? g.moveTo(x + rad * Math.cos(a), y + rad * Math.sin(a)) : g.lineTo(x + rad * Math.cos(a), y + rad * Math.sin(a));
                }
                g.closePath(); g.fillStyle = color; g.fill(); break;
            case 'cross': {
                const t = Math.max(2, size * 0.28);
                g.fillStyle = color;
                g.fillRect(x - size, y - t, size * 2, t * 2);
                g.fillRect(x - t, y - size, t * 2, size * 2); break;
            }
        }
        g.restore();
    }

    function renderOrbit(g, w, h) {
        if (!orbitState.initialized) initOrbit(w, h);
        const size = 28;
        const color = cfg().color;
        for (const pos of orbitState.visible) {
            drawOrbitShape(g, pos.x, pos.y, pos.shape, size, color);
        }
        const { cx, cy } = extents(w, h);
        drawStimulus(g, cx, cy);
    }

    // ─── Random Numbers ───────────────────────────────────────────────────────

    function getNumberPos(w, h, existing, minDist) {
        const pad = 0.09;
        for (let attempt = 0; attempt < 25; attempt++) {
            const x = (pad + Math.random() * (1 - pad * 2)) * w;
            const y = (pad + Math.random() * (1 - pad * 2)) * h;
            let ok = true;
            for (const e of existing) {
                if (Math.hypot(x - e.x, y - e.y) < minDist) { ok = false; break; }
            }
            if (ok) return { x, y };
        }
        return { x: (pad + Math.random() * (1 - pad * 2)) * w, y: (pad + Math.random() * (1 - pad * 2)) * h };
    }

    function initNumbers(w, h) {
        numbersState.visible = [];
        numbersState.holdRemaining = 2.0 / speedFactor();
        numbersState.initialized = true;
    }

    function updateNumbers(dt, w, h) {
        if (cfg().exerciseType !== 'random_numbers') return;
        if (!numbersState.initialized) initNumbers(w, h);
        numbersState.holdRemaining -= dt;
        if (numbersState.holdRemaining <= 0) {
            if (numbersState.visible.length < NUMBER_COUNT) {
                const fontSize = Math.max(18, cfg().size * 1.6);
                const pos = getNumberPos(w, h, numbersState.visible, fontSize * 1.8);
                numbersState.visible.push({ x: pos.x, y: pos.y, value: Math.floor(Math.random() * 30) + 1 });
            } else {
                initNumbers(w, h);
            }
            numbersState.holdRemaining = 2.0 / speedFactor();
        }
    }

    function renderRandomNumbers(g, w, h) {
        if (!numbersState.initialized) initNumbers(w, h);
        const color = cfg().color;
        const fontSize = Math.max(18, cfg().size * 1.8);
        g.save();
        g.font = `bold ${fontSize}px Inter, system-ui, monospace`;
        g.textAlign = 'center';
        g.textBaseline = 'middle';
        g.fillStyle = color;
        for (const item of numbersState.visible) {
            g.fillText(String(item.value), item.x, item.y);
        }
        g.restore();
    }

    // ─── Dual Bounce ──────────────────────────────────────────────────────────

    function initDualBounce(w, h) {
        const m = margin(), speed = speedFactor() * 280;
        const a1 = Math.random() * Math.PI * 2, a2 = Math.random() * Math.PI * 2;
        dualBounceState.a = {
            x: m + Math.random() * (w - m * 2), y: m + Math.random() * (h - m * 2),
            vx: Math.cos(a1) * speed, vy: Math.sin(a1) * speed, speedRatio: 1.0,
        };
        dualBounceState.b = {
            x: m + Math.random() * (w - m * 2), y: m + Math.random() * (h - m * 2),
            vx: Math.cos(a2) * speed * 0.85, vy: Math.sin(a2) * speed * 0.85, speedRatio: 0.85,
        };
        dualBounceState.initialized = true;
    }

    function updateDualBounce(dt, w, h) {
        if (cfg().exerciseType !== 'dual_bounce') return;
        if (!dualBounceState.initialized) initDualBounce(w, h);
        const baseSpeed = speedFactor() * 280;
        const m = margin();
        for (const p of [dualBounceState.a, dualBounceState.b]) {
            const currentSpeed = Math.hypot(p.vx, p.vy);
            if (currentSpeed > 0) {
                const targetSpeed = baseSpeed * p.speedRatio;
                const scale = targetSpeed / currentSpeed;
                p.vx *= scale; p.vy *= scale;
            }
            p.x += p.vx * dt; p.y += p.vy * dt;
            if (p.x <= m) { p.x = m; p.vx = Math.abs(p.vx); }
            if (p.x >= w - m) { p.x = w - m; p.vx = -Math.abs(p.vx); }
            if (p.y <= m) { p.y = m; p.vy = Math.abs(p.vy); }
            if (p.y >= h - m) { p.y = h - m; p.vy = -Math.abs(p.vy); }
        }
    }

    function renderDualBounce(g, w, h) {
        if (!dualBounceState.initialized) initDualBounce(w, h);
        const stimType = cfg().stimulusType === 'emoji' ? 'dot' : cfg().stimulusType;
        drawStimulus(g, dualBounceState.a.x, dualBounceState.a.y, cfg().color, stimType);
        drawStimulus(g, dualBounceState.b.x, dualBounceState.b.y, '#ef4444', stimType);
    }

    // ─── Zigzag Numbers ──────────────────────────────────────────────────────

    function getZigzagNumBallPos(progress, w, h) {
        const m = margin();
        const { cy } = extents(w, h);
        const ampY = h / 2 - m;
        // X: avanza de izquierda a derecha
        const x = m + progress * (w - 2 * m);
        // Y: triangle wave (zigzag arriba/abajo), empieza en el centro
        const t = progress * ZN_ZIGS + 0.5;
        const frac = t % 1;
        const seg = Math.floor(t) % 2;
        const y = cy + (seg === 0 ? -ampY + frac * 2 * ampY : ampY - frac * 2 * ampY);
        return { x, y };
    }

    function initZigzagNum(w, h) {
        zigzagNumState.direction = 1;
        zigzagNumState.progress = 0;
        zigzagNumState.numbers = [];
        zigzagNumState.numTimer = 0;
        zigzagNumState.ball = getZigzagNumBallPos(0, w, h);
        zigzagNumState.initialized = true;
    }

    function spawnZigzagNumber(w, h) {
        const fontSize = Math.max(18, cfg().size * 1.6);
        const minDist = fontSize * 2.2;
        for (let attempt = 0; attempt < 30; attempt++) {
            const x = (0.07 + Math.random() * 0.86) * w;
            const y = (0.07 + Math.random() * 0.86) * h;
            let ok = true;
            for (const e of zigzagNumState.numbers) {
                if (Math.hypot(x - e.x, y - e.y) < minDist) { ok = false; break; }
            }
            if (ok) { zigzagNumState.numbers.push({ x, y, value: Math.floor(Math.random() * 30) + 1 }); return; }
        }
    }

    function updateZigzagNum(dt, w, h) {
        if (cfg().exerciseType !== 'zigzag_numbers') return;
        if (!zigzagNumState.initialized) initZigzagNum(w, h);
        const sf = speedFactor();
        const cycleDuration = 7 / sf;
        zigzagNumState.progress += (dt / cycleDuration) * zigzagNumState.direction;
        if (zigzagNumState.progress >= 1) {
            zigzagNumState.progress = 1;
            zigzagNumState.direction = -1;
        } else if (zigzagNumState.progress <= 0) {
            zigzagNumState.progress = 0;
            zigzagNumState.direction = 1;
            zigzagNumState.numbers = [];
            zigzagNumState.numTimer = 0;
        }
        zigzagNumState.ball = getZigzagNumBallPos(zigzagNumState.progress, w, h);
        const numInterval = 0.6 / sf;
        zigzagNumState.numTimer += dt;
        if (zigzagNumState.numTimer >= numInterval && zigzagNumState.numbers.length < 40) {
            zigzagNumState.numTimer -= numInterval;
            spawnZigzagNumber(w, h);
        }
    }

    function renderZigzagNum(g, w, h) {
        if (!zigzagNumState.initialized) initZigzagNum(w, h);
        const fontSize = Math.max(18, cfg().size * 1.6);
        g.save();
        g.font = `bold ${fontSize}px Inter, system-ui, monospace`;
        g.textAlign = 'center';
        g.textBaseline = 'middle';
        g.fillStyle = cfg().color;
        for (const num of zigzagNumState.numbers) {
            g.fillText(String(num.value), num.x, num.y);
        }
        g.restore();
        const stimType = cfg().stimulusType === 'emoji' ? 'dot' : cfg().stimulusType;
        drawStimulus(g, zigzagNumState.ball.x, zigzagNumState.ball.y, cfg().color, stimType);
    }

    // ─── Four Pulse ──────────────────────────────────────────────────────────

    function renderFourPulse(g, w, h) {
        const baseSize = cfg().size;
        const minSize = Math.max(2, baseSize * 0.25);
        const maxSize = baseSize * 2.5;
        const sf = speedFactor();
        const freq = sf * 1.5;
        const PI = Math.PI;
        const stimType = cfg().stimulusType === 'emoji' ? 'dot' : cfg().stimulusType;

        // Cuatro puntos fijos: 2 arriba, 2 abajo.
        // Fase π/2  → empieza en MAX (enchicándose primero).
        // Fase 3π/2 → empieza en MIN (dilatándose primero).
        const dots = [
            { x: w * 0.25, y: h * 0.3,  phase: PI / 2 },       // arriba-izq:  empieza grande (enchicándose)
            { x: w * 0.75, y: h * 0.3,  phase: 3 * PI / 2 },   // arriba-der:  empieza chico  (dilatándose)
            { x: w * 0.25, y: h * 0.7,  phase: 3 * PI / 2 },   // abajo-izq:   opuesto a arriba-izq
            { x: w * 0.75, y: h * 0.7,  phase: PI / 2 },        // abajo-der:   opuesto a arriba-der
        ];

        for (const dot of dots) {
            const size = minSize + (maxSize - minSize) * (0.5 + 0.5 * Math.sin(elapsedTime * freq + dot.phase));
            drawStimulus(g, dot.x, dot.y, cfg().color, stimType, size);
        }
    }

    // ─── Rendering ────────────────────────────────────────────────────────────

    function clearCanvas(color = '#0f172a') {
        const c = canvas(), g = ctx();
        if (!c || !g) return;
        g.fillStyle = color;
        g.fillRect(0, 0, c.width, c.height);
    }

    function drawStimulus(g, x, y, colorOverride, stimTypeOverride, sizeOverride) {
        const size = sizeOverride ?? cfg().size, color = colorOverride ?? cfg().color;
        const stimType = stimTypeOverride ?? cfg().stimulusType;
        g.save();

        const grd = g.createRadialGradient(x, y, 0, x, y, size * 2.5);
        grd.addColorStop(0, color + '55');
        grd.addColorStop(1, 'transparent');
        g.beginPath();
        g.arc(x, y, size * 2.5, 0, Math.PI * 2);
        g.fillStyle = grd;
        g.fill();

        switch (stimType) {
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
        if (['saccade', 'zigzag', 'particles', 'orbit_shapes', 'random_numbers', 'dual_bounce', 'four_pulse', 'zigzag_numbers'].includes(type)) return;

        const sf = speedFactor();
        const isBee = ['bee_h', 'bee_v'].includes(type);
        const isSpiral = type === 'spiral';
        const isComplex = ['cruz', 'equis'].includes(type);
        const isBounce = ['circular_bounce', 's_curve'].includes(type);

        const period = isBee || isBounce ?
            (Math.PI * 4) / Math.max(0.01, sf) :
            isSpiral ?
            (Math.PI * 2) / Math.max(0.01, sf * 0.25) :
            (Math.PI * 2) / Math.max(0.01, sf);

        const steps = isComplex ?
            Math.floor(700 * sf) :
            isBee || isBounce ? 800 :
            isSpiral ? 600 :
            120;

        g.save();
        g.beginPath();
        for (let i = 0; i <= steps; i++) {
            const p = computePosition((i / steps) * period);
            i === 0 ? g.moveTo(p.x, p.y) : g.lineTo(p.x, p.y);
        }
        // cruz/equis no se cierran con closePath porque genera líneas extra
        if (!isComplex) g.closePath();
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
            const size = cfg().size;
            particlesState.items.forEach((p, i) => {
                const color = i === 0 ? '#f472b6' : '#60a5fa';
                g.save();
                const grd = g.createRadialGradient(p.x, p.y, 0, p.x, p.y, size * 2.5);
                grd.addColorStop(0, color + '44');
                grd.addColorStop(1, 'transparent');
                g.beginPath(); g.arc(p.x, p.y, size * 2.5, 0, Math.PI * 2);
                g.fillStyle = grd; g.fill();
                g.beginPath(); g.arc(p.x, p.y, size, 0, Math.PI * 2);
                g.fillStyle = color; g.fill();
                g.restore();
            });
        } else if (cfg().exerciseType === 'orbit_shapes') {
            renderOrbit(g, c.width, c.height);
        } else if (cfg().exerciseType === 'random_numbers') {
            renderRandomNumbers(g, c.width, c.height);
        } else if (cfg().exerciseType === 'dual_bounce') {
            renderDualBounce(g, c.width, c.height);
        } else if (cfg().exerciseType === 'four_pulse') {
            renderFourPulse(g, c.width, c.height);
        } else if (cfg().exerciseType === 'zigzag_numbers') {
            renderZigzagNum(g, c.width, c.height);
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
            if (c) { updateSaccade(dt); updateZigzag(dt, c.width, c.height); updateParticles(dt, c.width, c.height); updateOrbit(dt, c.width, c.height); updateNumbers(dt, c.width, c.height); updateDualBounce(dt, c.width, c.height); updateZigzagNum(dt, c.width, c.height); }

            if (cfg().duration > 0 && elapsedTime >= cfg().duration) { stop(); return; }
        }
        lastTimestamp = timestamp;
        renderFrame();
        rafId = requestAnimationFrame(tick);
    }

    // ─── Public controls ──────────────────────────────────────────────────────

    function start() {
        if (state !== 'idle' && state !== 'stopped') return;
        elapsedTime = 0; elapsedSeconds = 0; lastTimestamp = null;
        saccade.initialized = false; zigzag.initialized = false;
        particlesState.initialized = false; particlesState.items = []; particlesState.lastSf = 0;
        orbitState.initialized = false;
        numbersState.initialized = false; numbersState.visible = [];
        dualBounceState.initialized = false;
        zigzagNumState.initialized = false; zigzagNumState.numbers = [];
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
        particlesState.initialized = false; particlesState.items = []; particlesState.lastSf = 0;
        orbitState.initialized = false;
        numbersState.initialized = false; numbersState.visible = [];
        dualBounceState.initialized = false;
        zigzagNumState.initialized = false; zigzagNumState.numbers = [];
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
