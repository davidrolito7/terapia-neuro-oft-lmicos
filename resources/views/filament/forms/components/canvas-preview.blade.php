<div
    wire:ignore
    x-data="exerciseCanvas()"
    x-init="init()"
    class="relative w-full rounded-xl overflow-hidden bg-slate-900 border border-slate-700"
    style="height: max(560px, min(70vh, 760px)); width: 100%;">
    <canvas x-ref="canvas" class="absolute inset-0 w-full h-full block"></canvas>

    <div class="absolute top-3 right-3 flex items-center gap-2 z-10">
        <button
            type="button"
            @click="togglePlay()"
            class="flex items-center justify-center w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white transition-colors backdrop-blur-sm"
            :title="running ? 'Pausar' : 'Reproducir'">
            <svg x-show="!running" class="w-4 h-4 translate-x-px" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
            </svg>
            <svg x-show="running" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    <div class="absolute bottom-3 right-3 z-10">
        <span class="w-2 h-2 rounded-full block"
            :class="running ? 'bg-green-400 animate-pulse' : 'bg-slate-600'"></span>
    </div>
</div>

<script>
    (function() {
        if (window.exerciseCanvas) return;

        window.exerciseCanvas = function() {
            return {
                running: false,
                rafId: null,
                t: 0,
                lastTs: null,

                // ── Zigzag state ──────────────────────────────────────────────────
                zz: { x: 0, y: 0, vx: 0, vy: 0, init: false },

                // ── Saccade state ─────────────────────────────────────────────────
                sc: { cur: { x: 0, y: 0 }, nxt: { x: 0, y: 0 }, hold: 0, init: false },

                // ── Particles state ───────────────────────────────────────────────
                pt: { items: [], init: false, initSf: 0 },

                // ── Orbit state ───────────────────────────────────────────────────
                orb: { init: false, pending: [], visible: [], hold: 0 },

                // ── Random Numbers state ──────────────────────────────────────────
                nb: { init: false, visible: [], hold: 0 },

                // ── Dual Bounce state ─────────────────────────────────────────────
                db: { init: false, a: { x: 0, y: 0, vx: 0, vy: 0 }, b: { x: 0, y: 0, vx: 0, vy: 0 } },

                // ── Zigzag Numbers state ──────────────────────────────────────────
                zn: { init: false, direction: 1, progress: 0, ball: { x: 0, y: 0 }, numbers: [], numTimer: 0 },
                ZN_ZIGS: 5,

                ORBIT_SHAPES: ['dot', 'triangle', 'square', 'diamond', 'pentagon', 'hexagon', 'star', 'cross'],

                // ─────────────────────────────────────────────────────────────────

                getConfig() {
                    const root = this.$el.closest('.fi-fo-repeater-item') ||
                        this.$el.closest('[data-repeater-item]') ||
                        this.$el.parentElement;

                    const sel = (s) => root.querySelector(`select[id$="${s}"], select[name$="${s}"]`);
                    const inp = (s) => root.querySelector(`input[id$="${s}"], input[name*="${s}"]`);
                    const radio = (s) => root.querySelector(`input[type="radio"][name*="${s}"]:checked`);

                    return {
                        exerciseType: sel('tipo_ejercicio')?.value || 'circular',
                        stimulusType: sel('tipo_estimulo')?.value || 'dot',
                        emoji: (radio('emoji_estimulo') || inp('emoji_estimulo'))?.value || '⭐',
                        speed: parseFloat(inp('velocidad')?.value || 5),
                        size: parseFloat(inp('tamano')?.value || 20),
                        color: inp('color')?.value || '#22d3ee',
                    };
                },

                sf(cfg) {
                    return 0.3 + ((cfg.speed - 1) * 1.5) / 9;
                },

                extents(w, h, cfg) {
                    const m = cfg.size + 8;
                    return {
                        cx: w / 2,
                        cy: h / 2,
                        ex: w / 2 - m,
                        ey: h / 2 - m,
                        r: Math.min(w / 2 - m, h / 2 - m),
                        m,
                    };
                },

                rnd(min, max) {
                    return min + Math.random() * (max - min);
                },

                // ── Zigzag ────────────────────────────────────────────────────────
                initZigzag(w, h, cfg) {
                    const {
                        m
                    } = this.extents(w, h, cfg);
                    const spd = this.sf(cfg) * 280;
                    this.zz = {
                        x: m,
                        y: h / 2,
                        vx: spd,
                        vy: spd * 0.7,
                        init: true,
                        minX: m,
                        maxX: w - m,
                        minY: m,
                        maxY: h - m
                    };
                },
                updateZigzag(dt, w, h, cfg) {
                    if (!this.zz.init) this.initZigzag(w, h, cfg);
                    const z = this.zz;
                    z.maxX = w - (cfg.size + 8);
                    z.maxY = h - (cfg.size + 8);
                    z.x += z.vx * dt;
                    z.y += z.vy * dt;
                    if (z.x <= z.minX) {
                        z.x = z.minX;
                        z.vx = Math.abs(z.vx);
                    }
                    if (z.x >= z.maxX) {
                        z.x = z.maxX;
                        z.vx = -Math.abs(z.vx);
                    }
                    if (z.y <= z.minY) {
                        z.y = z.minY;
                        z.vy = Math.abs(z.vy);
                    }
                    if (z.y >= z.maxY) {
                        z.y = z.maxY;
                        z.vy = -Math.abs(z.vy);
                    }
                },

                // ── Saccade ───────────────────────────────────────────────────────
                rndPos(w, h, m) {
                    return {
                        x: this.rnd(m, w - m),
                        y: this.rnd(m, h - m)
                    };
                },
                initSaccade(w, h, cfg) {
                    const m = cfg.size + 8;
                    this.sc = {
                        cur: this.rndPos(w, h, m),
                        nxt: this.rndPos(w, h, m),
                        hold: 2.5 / this.sf(cfg),
                        init: true
                    };
                },
                updateSaccade(dt, w, h, cfg) {
                    if (!this.sc.init) this.initSaccade(w, h, cfg);
                    this.sc.hold -= dt;
                    if (this.sc.hold <= 0) {
                        this.sc.cur = {
                            ...this.sc.nxt
                        };
                        this.sc.nxt = this.rndPos(w, h, cfg.size + 8);
                        this.sc.hold = 2.5 / this.sf(cfg);
                    }
                },

                // ── Particles ─────────────────────────────────────────────────────
                initParticles(w, h, cfg) {
                    const m = cfg.size + 8;
                    const sf = this.sf(cfg);
                    const spd = sf * 220;
                    this.pt.initSf = sf;
                    this.pt.items = Array.from({
                        length: 25
                    }, (_, i) => {
                        const a = Math.random() * Math.PI * 2;
                        const s = i === 0 ? spd * 1.5 : spd * (0.4 + Math.random() * 0.4);
                        return {
                            x: this.rnd(m, w - m),
                            y: this.rnd(m, h - m),
                            vx: Math.cos(a) * s,
                            vy: Math.sin(a) * s
                        };
                    });
                    this.pt.init = true;
                },
                updateParticles(dt, w, h, cfg) {
                    if (this.pt.init && this.sf(cfg) !== this.pt.initSf) this.pt.init = false;
                    if (!this.pt.init) this.initParticles(w, h, cfg);
                    const m = cfg.size + 8;
                    for (let i = 0; i < this.pt.items.length; i++) {
                        const p = this.pt.items[i];
                        p.x += p.vx * dt;
                        p.y += p.vy * dt;
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
                },

                // ── Helpers para figuras poligonales ─────────────────────────────
                walkPath(v, t, sf) {
                    const n = v.length,
                        TAU = Math.PI * 2;
                    const cycle = (t * sf) % TAU,
                        segLen = TAU / n;
                    const side = Math.min(Math.floor(cycle / segLen), n - 1),
                        frac = (cycle % segLen) / segLen;
                    const a = v[side],
                        b = v[(side + 1) % n];
                    return {
                        x: a.x + (b.x - a.x) * frac,
                        y: a.y + (b.y - a.y) * frac
                    };
                },

                // Igual que walkPath pero reparte el tiempo proporcional a la longitud de cada segmento
                walkPathArc(v, t, sf) {
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
                },

                walkPathBounce(v, t, sf) {
                    const n = v.length,
                        TAU = Math.PI * 2;
                    const tNorm = (t * sf) % (TAU * 2);
                    const cycle = tNorm <= TAU ? tNorm : TAU * 2 - tNorm;
                    const segLen = TAU / n;
                    const side = Math.min(Math.floor(cycle / segLen), n - 1),
                        frac = (cycle % segLen) / segLen;
                    const a = v[side],
                        b = v[(side + 1) % n];
                    return {
                        x: a.x + (b.x - a.x) * frac,
                        y: a.y + (b.y - a.y) * frac
                    };
                },

                regularPoly(n, cx, cy, r) {
                    return Array.from({
                        length: n
                    }, (_, i) => ({
                        x: cx + r * Math.cos(-Math.PI / 2 + Math.PI * 2 * i / n),
                        y: cy + r * Math.sin(-Math.PI / 2 + Math.PI * 2 * i / n),
                    }));
                },

                // ── Posición determinista (tipos que usan t) ──────────────────────
                computePos(w, h, cfg, t) {
                    const {
                        cx,
                        cy,
                        ex,
                        ey,
                        r
                    } = this.extents(w, h, cfg);
                    const sf = this.sf(cfg);
                    if (r <= 0) return {
                        x: cx,
                        y: cy
                    };

                    switch (cfg.exerciseType) {
                        case 'circular':
                            return {
                                x: cx + r * Math.cos(t * sf), y: cy + r * Math.sin(t * sf)
                            };
                        case 'circular_ccw':
                            return {
                                x: cx + r * Math.cos(-t * sf), y: cy + r * Math.sin(-t * sf)
                            };
                        case 'figure8':
                            return {
                                x: cx + ex * Math.sin(2 * t * sf), y: cy + ey * Math.sin(t * sf)
                            };
                        case 'figure8_ccw':
                            return {
                                x: cx + ex * Math.sin(-2 * t * sf), y: cy + ey * Math.sin(-t * sf)
                            };
                        case 'figure8_v':
                            return {
                                x: cx + ex * Math.sin(t * sf), y: cy + ey * Math.sin(2 * t * sf)
                            };
                        case 'horizontal':
                            return {
                                x: cx + ex * Math.cos(t * sf), y: cy
                            };
                        case 'vertical':
                            return {
                                x: cx, y: cy + ey * Math.sin(t * sf)
                            };
                        case 'vertical_rev':
                            return {
                                x: cx, y: cy - ey * Math.sin(t * sf)
                            };
                        case 'diagonal': {
                            const p = Math.sin(t * sf);
                            return {
                                x: cx + ex * p,
                                y: cy + ey * p
                            };
                        }
                        case 'diagonal_tr': {
                            const p = Math.sin(t * sf);
                            return {
                                x: cx + ex * p,
                                y: cy - ey * p
                            };
                        }
                        case 'triangular': {
                            const v = [{
                                x: cx,
                                y: cy - r
                            }, {
                                x: cx + r * .866,
                                y: cy + r * .5
                            }, {
                                x: cx - r * .866,
                                y: cy + r * .5
                            }];
                            const cyc = (t * sf) % (Math.PI * 2),
                                seg = (Math.PI * 2) / 3;
                            const s = Math.min(Math.floor(cyc / seg), 2),
                                f = (cyc % seg) / seg;
                            return {
                                x: v[s].x + (v[(s + 1) % 3].x - v[s].x) * f,
                                y: v[s].y + (v[(s + 1) % 3].y - v[s].y) * f
                            };
                        }
                        case 'square': {
                            const c = [{
                                x: cx - ex,
                                y: cy - ey
                            }, {
                                x: cx + ex,
                                y: cy - ey
                            }, {
                                x: cx + ex,
                                y: cy + ey
                            }, {
                                x: cx - ex,
                                y: cy + ey
                            }];
                            const cyc = (t * sf) % (Math.PI * 2),
                                seg = (Math.PI * 2) / 4;
                            const s = Math.min(Math.floor(cyc / seg), 3),
                                f = (cyc % seg) / seg;
                            return {
                                x: c[s].x + (c[(s + 1) % 4].x - c[s].x) * f,
                                y: c[s].y + (c[(s + 1) % 4].y - c[s].y) * f
                            };
                        }
                        case 'spiral': {
                            const pf = sf * 0.25;
                            const s = Math.abs(Math.asin(Math.sin(t * pf)) / (Math.PI / 2));
                            const p = 0.09524,
                                q = 0.90476;
                            const rNorm = (-p + Math.sqrt(p * p + 4 * q * s)) / (2 * q);
                            const rad = r * 0.05 + r * 0.95 * rNorm;
                            const angle = rNorm * Math.PI * 6;
                            return {
                                x: cx + rad * Math.cos(angle),
                                y: cy + rad * Math.sin(angle)
                            };
                        }
                        case 'spring': {
                            return {
                                x: cx + ex * Math.cos(t * sf * 0.55),
                                y: cy + ey * Math.sin(t * sf * 5.5)
                            };
                        }
                        case 'bee_h': {
                            const loops = 5;
                            const TAU = Math.PI * 2;

                            const tNorm = (t * sf) % (TAU * 2);

                            // ida y vuelta suave
                            const p = tNorm <= TAU ?
                                tNorm :
                                TAU * 2 - tNorm;

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

                            // ida y vuelta suave
                            const p = tNorm <= TAU ?
                                tNorm :
                                TAU * 2 - tNorm;

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
                            return {
                                x: cx + ex * tri,
                                y: cy + ey * 0.88 * (1 - 2 * arch)
                            };
                        }
                        case 'wave_h_inv': {
                            const tri = Math.asin(Math.sin(t * sf)) / (Math.PI / 2);
                            const arch = Math.cos(tri * Math.PI / 2);
                            return {
                                x: cx + ex * tri,
                                y: cy - ey * 0.88 * (1 - 2 * arch)
                            };
                        }
                        case 'pentagon':
                            return this.walkPath(this.regularPoly(5, cx, cy, r), t, sf);
                        case 'hexagon':
                            return this.walkPath(this.regularPoly(6, cx, cy, r), t, sf);
                        case 'arrow_bi': {
                            const aw = ex * 0.3,
                                bh = ey * 0.32;
                            return this.walkPath([{
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
                            return this.walkPath([{
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

                            return this.walkPath([{
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
                        case 'star_path': {
                            const outerR = r,
                                innerR = r * 0.4;
                            const pts = Array.from({
                                length: 10
                            }, (_, i) => {
                                const angle = -Math.PI / 2 + (Math.PI * 2 * i) / 10;
                                const rad = i % 2 === 0 ? outerR : innerR;
                                return {
                                    x: cx + rad * Math.cos(angle),
                                    y: cy + rad * Math.sin(angle)
                                };
                            });
                            return this.walkPath(pts, t, sf);
                        }
                        case 'hourglass': {
                            return this.walkPathArc([{
                                    x: cx - ex,
                                    y: cy - ey
                                },
                                {
                                    x: cx + ex,
                                    y: cy - ey
                                },
                                {
                                    x: cx,
                                    y: cy
                                },
                                {
                                    x: cx + ex,
                                    y: cy + ey
                                },
                                {
                                    x: cx - ex,
                                    y: cy + ey
                                },
                                {
                                    x: cx,
                                    y: cy
                                },
                            ], t, sf);
                        }
                        case 'circular_bounce': {
                            const TAU = Math.PI * 2;
                            const tNorm = (t * sf) % (TAU * 2);
                            const angle = tNorm <= TAU ? tNorm - Math.PI / 2 : (TAU * 2 - tNorm) - Math.PI / 2;
                            return {
                                x: cx + r * Math.cos(angle),
                                y: cy + r * Math.sin(angle)
                            };
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
                        // zigzag, saccade, particles — posición manejada por estado
                        case 'zigzag':
                            return {
                                x: this.zz.x || cx, y: this.zz.y || cy
                            };
                        case 'saccade':
                            return {
                                x: this.sc.cur?.x ?? cx, y: this.sc.cur?.y ?? cy
                            };
                        case 'particles':
                            return this.pt.items[0] ?? {
                                x: cx,
                                y: cy
                            };
                        default:
                            return {
                                x: cx, y: cy
                            };
                    }
                },

                // ── Orbit Shapes ──────────────────────────────────────────────────
                initOrbit(w, h, cfg) {
                    const { cx, cy, r } = this.extents(w, h, cfg);
                    const shapes = [...this.ORBIT_SHAPES];
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
                    this.orb.pending = positions;
                    this.orb.visible = [];
                    this.orb.hold = 2.0 / this.sf(cfg);
                    this.orb.init = true;
                },
                updateOrbit(dt, w, h, cfg) {
                    if (!this.orb.init) this.initOrbit(w, h, cfg);
                    this.orb.hold -= dt;
                    if (this.orb.hold <= 0) {
                        if (this.orb.pending.length > 0) {
                            this.orb.visible.push(this.orb.pending.shift());
                        } else {
                            this.initOrbit(w, h, cfg);
                        }
                        this.orb.hold = 2.0 / this.sf(cfg);
                    }
                },
                drawOrbitShape(ctx, x, y, shape, size, color) {
                    ctx.save();
                    switch (shape) {
                        case 'dot':
                            ctx.beginPath(); ctx.arc(x, y, size, 0, Math.PI * 2);
                            ctx.fillStyle = color; ctx.fill(); break;
                        case 'ring':
                            ctx.beginPath(); ctx.arc(x, y, size, 0, Math.PI * 2);
                            ctx.strokeStyle = color; ctx.lineWidth = Math.max(2, size * 0.25); ctx.stroke(); break;
                        case 'triangle':
                            ctx.beginPath();
                            for (let i = 0; i < 3; i++) {
                                const a = -Math.PI / 2 + Math.PI * 2 * i / 3;
                                i === 0 ? ctx.moveTo(x + size * Math.cos(a), y + size * Math.sin(a)) : ctx.lineTo(x + size * Math.cos(a), y + size * Math.sin(a));
                            }
                            ctx.closePath(); ctx.fillStyle = color; ctx.fill(); break;
                        case 'square':
                            ctx.fillStyle = color;
                            ctx.fillRect(x - size * 0.8, y - size * 0.8, size * 1.6, size * 1.6); break;
                        case 'diamond':
                            ctx.beginPath(); ctx.moveTo(x, y - size); ctx.lineTo(x + size * 0.7, y); ctx.lineTo(x, y + size); ctx.lineTo(x - size * 0.7, y);
                            ctx.closePath(); ctx.fillStyle = color; ctx.fill(); break;
                        case 'pentagon':
                            ctx.beginPath();
                            for (let i = 0; i < 5; i++) {
                                const a = -Math.PI / 2 + Math.PI * 2 * i / 5;
                                i === 0 ? ctx.moveTo(x + size * Math.cos(a), y + size * Math.sin(a)) : ctx.lineTo(x + size * Math.cos(a), y + size * Math.sin(a));
                            }
                            ctx.closePath(); ctx.fillStyle = color; ctx.fill(); break;
                        case 'hexagon':
                            ctx.beginPath();
                            for (let i = 0; i < 6; i++) {
                                const a = Math.PI / 6 + Math.PI * 2 * i / 6;
                                i === 0 ? ctx.moveTo(x + size * Math.cos(a), y + size * Math.sin(a)) : ctx.lineTo(x + size * Math.cos(a), y + size * Math.sin(a));
                            }
                            ctx.closePath(); ctx.fillStyle = color; ctx.fill(); break;
                        case 'star':
                            ctx.beginPath();
                            for (let i = 0; i < 10; i++) {
                                const a = i * Math.PI / 5 - Math.PI / 2;
                                const rad = i % 2 === 0 ? size : size * 0.4;
                                i === 0 ? ctx.moveTo(x + rad * Math.cos(a), y + rad * Math.sin(a)) : ctx.lineTo(x + rad * Math.cos(a), y + rad * Math.sin(a));
                            }
                            ctx.closePath(); ctx.fillStyle = color; ctx.fill(); break;
                        case 'cross': {
                            const t = Math.max(2, size * 0.28);
                            ctx.fillStyle = color;
                            ctx.fillRect(x - size, y - t, size * 2, t * 2);
                            ctx.fillRect(x - t, y - size, t * 2, size * 2); break;
                        }
                    }
                    ctx.restore();
                },
                renderOrbit(ctx, w, h, cfg) {
                    if (!this.orb.init) this.initOrbit(w, h, cfg);
                    const size = 28;
                    const color = cfg.color;
                    for (const pos of this.orb.visible) {
                        this.drawOrbitShape(ctx, pos.x, pos.y, pos.shape, size, color);
                    }
                    const { cx, cy } = this.extents(w, h, cfg);
                    this.drawStimulus(ctx, cx, cy, cfg);
                },

                // ── Random Numbers ────────────────────────────────────────────────
                getNumberPos(w, h, existing, minDist) {
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
                },
                initNumbers(cfg) {
                    this.nb.visible = [];
                    this.nb.hold = 2.0 / this.sf(cfg);
                    this.nb.init = true;
                },
                updateNumbers(dt, w, h, cfg) {
                    if (!this.nb.init) this.initNumbers(cfg);
                    this.nb.hold -= dt;
                    if (this.nb.hold <= 0) {
                        if (this.nb.visible.length < 29) {
                            const fontSize = Math.max(18, cfg.size * 1.6);
                            const pos = this.getNumberPos(w, h, this.nb.visible, fontSize * 1.8);
                            this.nb.visible.push({ x: pos.x, y: pos.y, value: Math.floor(Math.random() * 30) + 1 });
                        } else {
                            this.initNumbers(cfg);
                        }
                        this.nb.hold = 2.0 / this.sf(cfg);
                    }
                },
                renderRandomNumbers(ctx, w, h, cfg) {
                    if (!this.nb.init) this.initNumbers(cfg);
                    const fontSize = Math.max(18, cfg.size * 1.8);
                    ctx.save();
                    ctx.font = `bold ${fontSize}px Inter, system-ui, monospace`;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = cfg.color;
                    for (const item of this.nb.visible) {
                        ctx.fillText(String(item.value), item.x, item.y);
                    }
                    ctx.restore();
                },

                // ── Dual Bounce ───────────────────────────────────────────────────
                initDualBounce(w, h, cfg) {
                    const m = cfg.size + 8, speed = this.sf(cfg) * 280;
                    const a1 = Math.random() * Math.PI * 2, a2 = Math.random() * Math.PI * 2;
                    this.db.a = { x: m + Math.random() * (w - m * 2), y: m + Math.random() * (h - m * 2), vx: Math.cos(a1) * speed, vy: Math.sin(a1) * speed, speedRatio: 1.0 };
                    this.db.b = { x: m + Math.random() * (w - m * 2), y: m + Math.random() * (h - m * 2), vx: Math.cos(a2) * speed * 0.85, vy: Math.sin(a2) * speed * 0.85, speedRatio: 0.85 };
                    this.db.init = true;
                },
                updateDualBounce(dt, w, h, cfg) {
                    if (!this.db.init) this.initDualBounce(w, h, cfg);
                    const baseSpeed = this.sf(cfg) * 280;
                    const m = cfg.size + 8;
                    for (const p of [this.db.a, this.db.b]) {
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
                },
                renderDualBounce(ctx, w, h, cfg) {
                    if (!this.db.init) this.initDualBounce(w, h, cfg);
                    const stimType = cfg.stimulusType === 'emoji' ? 'dot' : cfg.stimulusType;
                    this.drawStimulus(ctx, this.db.a.x, this.db.a.y, { ...cfg, stimulusType: stimType });
                    this.drawStimulus(ctx, this.db.b.x, this.db.b.y, { ...cfg, color: '#ef4444', stimulusType: stimType });
                },

                // ── Zigzag Numbers ────────────────────────────────────────────────
                getZigzagNumBallPos(progress, w, h, cfg) {
                    const m = cfg.size + 8;
                    const cy = h / 2;
                    const ampY = h / 2 - m;
                    // X: avanza de izquierda a derecha
                    const x = m + progress * (w - 2 * m);
                    // Y: triangle wave (zigzag arriba/abajo), empieza en centro
                    const t = progress * this.ZN_ZIGS + 0.5;
                    const frac = t % 1;
                    const seg = Math.floor(t) % 2;
                    const y = cy + (seg === 0 ? -ampY + frac * 2 * ampY : ampY - frac * 2 * ampY);
                    return { x, y };
                },
                initZigzagNum(w, h, cfg) {
                    this.zn.direction = 1;
                    this.zn.progress = 0;
                    this.zn.numbers = [];
                    this.zn.numTimer = 0;
                    this.zn.ball = this.getZigzagNumBallPos(0, w, h, cfg);
                    this.zn.init = true;
                },
                spawnZigzagNumber(w, h, cfg) {
                    const fontSize = Math.max(18, cfg.size * 1.6);
                    const minDist = fontSize * 2.2;
                    for (let attempt = 0; attempt < 30; attempt++) {
                        const x = (0.07 + Math.random() * 0.86) * w;
                        const y = (0.07 + Math.random() * 0.86) * h;
                        let ok = true;
                        for (const e of this.zn.numbers) {
                            if (Math.hypot(x - e.x, y - e.y) < minDist) { ok = false; break; }
                        }
                        if (ok) { this.zn.numbers.push({ x, y, value: Math.floor(Math.random() * 30) + 1 }); return; }
                    }
                },
                updateZigzagNum(dt, w, h, cfg) {
                    if (!this.zn.init) this.initZigzagNum(w, h, cfg);
                    const sf = this.sf(cfg);
                    const cycleDuration = 7 / sf;
                    this.zn.progress += (dt / cycleDuration) * this.zn.direction;
                    if (this.zn.progress >= 1) {
                        this.zn.progress = 1;
                        this.zn.direction = -1;
                    } else if (this.zn.progress <= 0) {
                        this.zn.progress = 0;
                        this.zn.direction = 1;
                        this.zn.numbers = [];
                        this.zn.numTimer = 0;
                    }
                    this.zn.ball = this.getZigzagNumBallPos(this.zn.progress, w, h, cfg);
                    const numInterval = 0.6 / sf;
                    this.zn.numTimer += dt;
                    if (this.zn.numTimer >= numInterval && this.zn.numbers.length < 40) {
                        this.zn.numTimer -= numInterval;
                        this.spawnZigzagNumber(w, h, cfg);
                    }
                },
                renderZigzagNum(ctx, w, h, cfg) {
                    if (!this.zn.init) this.initZigzagNum(w, h, cfg);
                    const fontSize = Math.max(18, cfg.size * 1.6);
                    ctx.save();
                    ctx.font = `bold ${fontSize}px Inter, system-ui, monospace`;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = cfg.color;
                    for (const num of this.zn.numbers) {
                        ctx.fillText(String(num.value), num.x, num.y);
                    }
                    ctx.restore();
                    const stimType = cfg.stimulusType === 'emoji' ? 'dot' : cfg.stimulusType;
                    this.drawStimulus(ctx, this.zn.ball.x, this.zn.ball.y, cfg, { stimulusType: stimType });
                },

                // ── Four Pulse ────────────────────────────────────────────────────
                renderFourPulse(ctx, w, h, cfg) {
                    const baseSize = cfg.size;
                    const minSize = Math.max(2, baseSize * 0.25);
                    const maxSize = baseSize * 2.5;
                    const sf = this.sf(cfg);
                    const freq = sf * 1.5;
                    const PI = Math.PI;
                    const stimType = cfg.stimulusType === 'emoji' ? 'dot' : cfg.stimulusType;

                    const dots = [
                        { x: w * 0.25, y: h * 0.3,  phase: PI / 2 },       // arriba-izq: grande → enchica
                        { x: w * 0.75, y: h * 0.3,  phase: 3 * PI / 2 },   // arriba-der: chico  → dilata
                        { x: w * 0.25, y: h * 0.7,  phase: 3 * PI / 2 },   // abajo-izq:  opuesto a arriba-izq
                        { x: w * 0.75, y: h * 0.7,  phase: PI / 2 },        // abajo-der:  opuesto a arriba-der
                    ];

                    for (const dot of dots) {
                        const size = minSize + (maxSize - minSize) * (0.5 + 0.5 * Math.sin(this.t * freq + dot.phase));
                        this.drawStimulus(ctx, dot.x, dot.y, cfg, { size, stimulusType: stimType });
                    }
                },

                // ── Ghost path ────────────────────────────────────────────────────
                drawGhostPath(ctx, w, h, cfg) {
                    // Tipos basados en estado no muestran trayectoria fantasma
                    if (['saccade', 'zigzag', 'particles', 'orbit_shapes', 'random_numbers', 'dual_bounce', 'four_pulse', 'zigzag_numbers'].includes(cfg.exerciseType)) {
                        return;
                    }

                    const sf = this.sf(cfg);

                    const isBee = ['bee_h', 'bee_v'].includes(cfg.exerciseType);
                    const isSpiral = cfg.exerciseType === 'spiral';
                    const isBounce = ['circular_bounce', 's_curve'].includes(cfg.exerciseType);

                    const period = isBee || isBounce ?
                        (Math.PI * 4) / Math.max(0.01, sf) :
                        isSpiral ?
                        (Math.PI * 2) / Math.max(0.01, sf * 0.25) :
                        (Math.PI * 2) / Math.max(0.01, sf);

                    const complexShapes = ['cruz', 'equis'];

                    const steps = complexShapes.includes(cfg.exerciseType) ?
                        Math.floor(700 * sf) :
                        isBee || isBounce ?
                        800 :
                        isSpiral ?
                        600 :
                        Math.floor(220 * sf);

                    ctx.save();

                    // Ayuda a que las líneas sean más uniformes
                    ctx.translate(0.5, 0.5);

                    ctx.beginPath();

                    for (let i = 0; i <= steps; i++) {
                        const p = this.computePos(
                            w,
                            h,
                            cfg,
                            (i / steps) * period
                        );

                        if (i === 0) {
                            ctx.moveTo(p.x, p.y);
                        } else {
                            ctx.lineTo(p.x, p.y);
                        }
                    }

                    // NO cerrar cruz/equis porque genera líneas extra
                    if (!complexShapes.includes(cfg.exerciseType)) {
                        ctx.closePath();
                    }

                    ctx.strokeStyle = cfg.color + '28';
                    ctx.lineWidth = 1.5;

                    // Mejora visual
                    ctx.lineJoin = 'round';
                    ctx.lineCap = 'round';

                    ctx.stroke();

                    ctx.restore();
                },

                // ── Stimulus ──────────────────────────────────────────────────────
                drawStimulus(ctx, x, y, cfg, cfgOverride) {
                    const c = cfgOverride ? { ...cfg, ...cfgOverride } : cfg;
                    const s = c.size, color = c.color;
                    ctx.save();

                    // Halo
                    const grd = ctx.createRadialGradient(x, y, 0, x, y, s * 2.5);
                    grd.addColorStop(0, color + '55');
                    grd.addColorStop(1, 'transparent');
                    ctx.beginPath();
                    ctx.arc(x, y, s * 2.5, 0, Math.PI * 2);
                    ctx.fillStyle = grd;
                    ctx.fill();

                    switch (c.stimulusType) {
                        case 'dot':
                            ctx.beginPath();
                            ctx.arc(x, y, s, 0, Math.PI * 2);
                            ctx.fillStyle = color;
                            ctx.fill();
                            break;
                        case 'ring':
                            ctx.beginPath();
                            ctx.arc(x, y, s, 0, Math.PI * 2);
                            ctx.strokeStyle = color;
                            ctx.lineWidth = Math.max(2, s * .25);
                            ctx.stroke();
                            break;
                        case 'star':
                            ctx.beginPath();
                            for (let i = 0; i < 10; i++) {
                                const a = i * Math.PI / 5 - Math.PI / 2;
                                const rad = i % 2 === 0 ? s : s * .4;
                                i === 0 ? ctx.moveTo(x + rad * Math.cos(a), y + rad * Math.sin(a)) :
                                    ctx.lineTo(x + rad * Math.cos(a), y + rad * Math.sin(a));
                            }
                            ctx.closePath();
                            ctx.fillStyle = color;
                            ctx.fill();
                            break;
                        case 'cross': {
                            const t2 = Math.max(2, s * .28);
                            ctx.fillStyle = color;
                            ctx.fillRect(x - s, y - t2, s * 2, t2 * 2);
                            ctx.fillRect(x - t2, y - s, t2 * 2, s * 2);
                            break;
                        }
                        case 'emoji':
                            ctx.font = `${s * 2.2}px Arial`;
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(c.emoji, x, y);
                            break;
                    }
                    ctx.restore();
                },

                // ── Draw frame ────────────────────────────────────────────────────
                drawFrame() {
                    const canvas = this.$refs.canvas;
                    if (!canvas || canvas.width === 0 || canvas.height === 0) return;
                    const cfg = this.getConfig();
                    const ctx = canvas.getContext('2d');
                    const w = canvas.width,
                        h = canvas.height;

                    // Fondo
                    ctx.fillStyle = '#0f172a';
                    ctx.fillRect(0, 0, w, h);

                    // Guías
                    ctx.save();
                    ctx.strokeStyle = 'rgba(255,255,255,0.04)';
                    ctx.lineWidth = 1;
                    ctx.setLineDash([4, 6]);
                    ctx.beginPath();
                    ctx.moveTo(w / 2, 0);
                    ctx.lineTo(w / 2, h);
                    ctx.moveTo(0, h / 2);
                    ctx.lineTo(w, h / 2);
                    ctx.stroke();
                    ctx.setLineDash([]);
                    ctx.restore();

                    // Trayectoria fantasma
                    this.drawGhostPath(ctx, w, h, cfg);

                    // Estímulo(s)
                    if (cfg.exerciseType === 'particles') {
                        if (!this.pt.init) this.initParticles(w, h, cfg);
                        const sz = cfg.size;
                        this.pt.items.forEach((p, i) => {
                            const color = i === 0 ? '#f472b6' : '#60a5fa';
                            ctx.save();
                            const grd = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, sz * 2.5);
                            grd.addColorStop(0, color + '44');
                            grd.addColorStop(1, 'transparent');
                            ctx.beginPath(); ctx.arc(p.x, p.y, sz * 2.5, 0, Math.PI * 2);
                            ctx.fillStyle = grd; ctx.fill();
                            ctx.beginPath(); ctx.arc(p.x, p.y, sz, 0, Math.PI * 2);
                            ctx.fillStyle = color; ctx.fill();
                            ctx.restore();
                        });
                    } else if (cfg.exerciseType === 'orbit_shapes') {
                        this.renderOrbit(ctx, w, h, cfg);
                    } else if (cfg.exerciseType === 'random_numbers') {
                        this.renderRandomNumbers(ctx, w, h, cfg);
                    } else if (cfg.exerciseType === 'dual_bounce') {
                        this.renderDualBounce(ctx, w, h, cfg);
                    } else if (cfg.exerciseType === 'four_pulse') {
                        this.renderFourPulse(ctx, w, h, cfg);
                    } else if (cfg.exerciseType === 'zigzag_numbers') {
                        this.renderZigzagNum(ctx, w, h, cfg);
                    } else {
                        const pos = this.computePos(w, h, cfg, this.t);
                        this.drawStimulus(ctx, pos.x, pos.y, cfg);
                    }
                },

                // ── Lifecycle ─────────────────────────────────────────────────────
                resetState() {
                    this.zz = { x: 0, y: 0, vx: 0, vy: 0, init: false };
                    this.sc = { cur: { x: 0, y: 0 }, nxt: { x: 0, y: 0 }, hold: 0, init: false };
                    this.pt = { items: [], init: false, initSf: 0 };
                    this.orb = { init: false, pending: [], visible: [], hold: 0 };
                    this.nb = { init: false, visible: [], hold: 0 };
                    this.db = { init: false, a: { x: 0, y: 0, vx: 0, vy: 0 }, b: { x: 0, y: 0, vx: 0, vy: 0 } };
                    this.zn = { init: false, direction: 1, progress: 0, ball: { x: 0, y: 0 }, numbers: [], numTimer: 0 };
                    this.t = 0;
                    this.lastTs = null;
                },

                tick(ts) {
                    if (!this.running) {
                        this.rafId = null;
                        return;
                    }
                    const canvas = this.$refs.canvas;
                    if (!canvas) {
                        this.rafId = null;
                        return;
                    }

                    const dt = this.lastTs !== null ? Math.min((ts - this.lastTs) / 1000, 0.1) : 0;
                    this.lastTs = ts;
                    this.t += dt;

                    const cfg = this.getConfig();
                    const w = canvas.width,
                        h = canvas.height;

                    this.updateZigzag(dt, w, h, cfg);
                    this.updateSaccade(dt, w, h, cfg);
                    this.updateParticles(dt, w, h, cfg);
                    this.updateOrbit(dt, w, h, cfg);
                    this.updateNumbers(dt, w, h, cfg);
                    this.updateDualBounce(dt, w, h, cfg);
                    if (cfg.exerciseType === 'zigzag_numbers') this.updateZigzagNum(dt, w, h, cfg);

                    this.drawFrame();
                    this.rafId = requestAnimationFrame((ts) => this.tick(ts));
                },

                start() {
                    this.resetState();
                    this.running = true;
                    this.rafId = requestAnimationFrame((ts) => this.tick(ts));
                },

                togglePlay() {
                    if (this.running) {
                        this.running = false;
                        if (this.rafId) cancelAnimationFrame(this.rafId);
                        this.rafId = null;
                    } else {
                        this.lastTs = null;
                        this.running = true;
                        this.rafId = requestAnimationFrame((ts) => this.tick(ts));
                    }
                },

                init() {
                    const canvas = this.$refs.canvas;
                    const sync = () => {
                        const w = this.$el.clientWidth || 800;
                        const h = this.$el.clientHeight || 560;
                        if (w > 0 && canvas.width !== w) {
                            canvas.width = w;
                            canvas.height = h;
                            if (!this.running) this.drawFrame();
                        }
                    };
                    new ResizeObserver(sync).observe(this.$el);
                    sync();
                    setTimeout(sync, 100);
                    setTimeout(sync, 300);
                    setTimeout(() => {
                        sync();
                        this.start();
                    }, 600);
                },
            };
        };
    })();
</script>