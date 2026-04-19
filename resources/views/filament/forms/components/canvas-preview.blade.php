<div
    wire:ignore
    x-data="exerciseCanvas()"
    x-init="init()"
    class="relative w-full rounded-xl overflow-hidden bg-slate-900 border border-slate-700"
    style="height: max(560px, min(70vh, 760px)); width: 100%;"
>
    <canvas x-ref="canvas" class="absolute inset-0 w-full h-full block"></canvas>

    <div class="absolute top-3 right-3 flex items-center gap-2 z-10">
        <button
            type="button"
            @click="togglePlay()"
            class="flex items-center justify-center w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white transition-colors backdrop-blur-sm"
            :title="running ? 'Pausar' : 'Reproducir'"
        >
            <svg x-show="!running" class="w-4 h-4 translate-x-px" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
            </svg>
            <svg x-show="running" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    <div class="absolute bottom-3 right-3 z-10">
        <span class="w-2 h-2 rounded-full block"
              :class="running ? 'bg-green-400 animate-pulse' : 'bg-slate-600'"></span>
    </div>
</div>

<script>
(function () {
    if (window.exerciseCanvas) return;

    window.exerciseCanvas = function () {
        return {
            running:   false,
            rafId:     null,
            t:         0,
            lastTs:    null,

            // ── Zigzag state ──────────────────────────────────────────────────
            zz: { x:0, y:0, vx:0, vy:0, init:false },

            // ── Saccade state ─────────────────────────────────────────────────
            sc: { cur:{ x:0,y:0 }, nxt:{ x:0,y:0 }, hold:0, init:false },

            // ── Particles state ───────────────────────────────────────────────
            pt: { items:[], init:false },

            // ─────────────────────────────────────────────────────────────────

            getConfig() {
                const root = this.$el.closest('.fi-fo-repeater-item')
                          || this.$el.closest('[data-repeater-item]')
                          || this.$el.parentElement;

                const sel   = (s) => root.querySelector(`select[id$="${s}"], select[name$="${s}"]`);
                const inp   = (s) => root.querySelector(`input[id$="${s}"], input[name*="${s}"]`);
                const radio = (s) => root.querySelector(`input[type="radio"][name*="${s}"]:checked`);

                return {
                    exerciseType: sel('tipo_ejercicio')?.value || 'circular',
                    stimulusType: sel('tipo_estimulo')?.value  || 'dot',
                    emoji:  (radio('emoji_estimulo') || inp('emoji_estimulo'))?.value || '⭐',
                    speed:  parseFloat(inp('velocidad')?.value || 5),
                    size:   parseFloat(inp('tamano')?.value    || 20),
                    color:  inp('color')?.value || '#22d3ee',
                };
            },

            sf(cfg) {
                return 0.3 + ((cfg.speed - 1) * 1.5) / 9;
            },

            extents(w, h, cfg) {
                const m = cfg.size + 8;
                return {
                    cx: w / 2, cy: h / 2,
                    ex: w / 2 - m, ey: h / 2 - m,
                    r:  Math.min(w / 2 - m, h / 2 - m),
                    m,
                };
            },

            rnd(min, max) { return min + Math.random() * (max - min); },

            // ── Zigzag ────────────────────────────────────────────────────────
            initZigzag(w, h, cfg) {
                const { m } = this.extents(w, h, cfg);
                const spd = this.sf(cfg) * 280;
                this.zz = { x: m, y: h/2, vx: spd, vy: spd * 0.7, init: true, minX: m, maxX: w-m, minY: m, maxY: h-m };
            },
            updateZigzag(dt, w, h, cfg) {
                if (!this.zz.init) this.initZigzag(w, h, cfg);
                const z = this.zz;
                z.maxX = w - (cfg.size + 8); z.maxY = h - (cfg.size + 8);
                z.x += z.vx * dt; z.y += z.vy * dt;
                if (z.x <= z.minX) { z.x = z.minX; z.vx =  Math.abs(z.vx); }
                if (z.x >= z.maxX) { z.x = z.maxX; z.vx = -Math.abs(z.vx); }
                if (z.y <= z.minY) { z.y = z.minY; z.vy =  Math.abs(z.vy); }
                if (z.y >= z.maxY) { z.y = z.maxY; z.vy = -Math.abs(z.vy); }
            },

            // ── Saccade ───────────────────────────────────────────────────────
            rndPos(w, h, m) { return { x: this.rnd(m, w-m), y: this.rnd(m, h-m) }; },
            initSaccade(w, h, cfg) {
                const m = cfg.size + 8;
                this.sc = { cur: this.rndPos(w,h,m), nxt: this.rndPos(w,h,m), hold: 2.5 / this.sf(cfg), init: true };
            },
            updateSaccade(dt, w, h, cfg) {
                if (!this.sc.init) this.initSaccade(w, h, cfg);
                this.sc.hold -= dt;
                if (this.sc.hold <= 0) {
                    this.sc.cur = { ...this.sc.nxt };
                    this.sc.nxt = this.rndPos(w, h, cfg.size + 8);
                    this.sc.hold = 2.5 / this.sf(cfg);
                }
            },

            // ── Particles ─────────────────────────────────────────────────────
            initParticles(w, h, cfg) {
                const m = cfg.size + 8;
                const spd = this.sf(cfg) * 220;
                this.pt.items = Array.from({ length: 5 }, () => {
                    const a = Math.random() * Math.PI * 2;
                    const s = spd * (0.5 + Math.random() * 0.8);
                    return { x: this.rnd(m,w-m), y: this.rnd(m,h-m), vx: Math.cos(a)*s, vy: Math.sin(a)*s };
                });
                this.pt.init = true;
            },
            updateParticles(dt, w, h, cfg) {
                if (!this.pt.init) this.initParticles(w, h, cfg);
                const m = cfg.size + 8;
                for (const p of this.pt.items) {
                    p.x += p.vx * dt; p.y += p.vy * dt;
                    if (p.x <= m)     { p.x = m;     p.vx =  Math.abs(p.vx); }
                    if (p.x >= w - m) { p.x = w - m; p.vx = -Math.abs(p.vx); }
                    if (p.y <= m)     { p.y = m;     p.vy =  Math.abs(p.vy); }
                    if (p.y >= h - m) { p.y = h - m; p.vy = -Math.abs(p.vy); }
                }
            },

            // ── Posición determinista (tipos que usan t) ──────────────────────
            computePos(w, h, cfg, t) {
                const { cx, cy, ex, ey, r } = this.extents(w, h, cfg);
                const sf = this.sf(cfg);
                if (r <= 0) return { x: cx, y: cy };

                switch (cfg.exerciseType) {
                    case 'circular':     return { x: cx + r*Math.cos(t*sf),     y: cy + r*Math.sin(t*sf) };
                    case 'circular_ccw': return { x: cx + r*Math.cos(-t*sf),    y: cy + r*Math.sin(-t*sf) };
                    case 'figure8':      return { x: cx + ex*Math.sin(2*t*sf),  y: cy + ey*Math.sin(t*sf) };
                    case 'figure8_ccw':  return { x: cx + ex*Math.sin(-2*t*sf), y: cy + ey*Math.sin(-t*sf) };
                    case 'figure8_v':    return { x: cx + ex*Math.sin(t*sf),    y: cy + ey*Math.sin(2*t*sf) };
                    case 'horizontal':   return { x: cx + ex*Math.cos(t*sf),    y: cy };
                    case 'vertical':     return { x: cx,                         y: cy + ey*Math.sin(t*sf) };
                    case 'vertical_rev': return { x: cx,                         y: cy - ey*Math.sin(t*sf) };
                    case 'diagonal': {
                        const p = Math.sin(t*sf);
                        return { x: cx + ex*p, y: cy + ey*p };
                    }
                    case 'diagonal_tr': {
                        const p = Math.sin(t*sf);
                        return { x: cx + ex*p, y: cy - ey*p };
                    }
                    case 'triangular': {
                        const v = [{ x:cx, y:cy-r },{ x:cx+r*.866, y:cy+r*.5 },{ x:cx-r*.866, y:cy+r*.5 }];
                        const cyc = (t*sf) % (Math.PI*2), seg = (Math.PI*2)/3;
                        const s = Math.min(Math.floor(cyc/seg), 2), f = (cyc%seg)/seg;
                        return { x: v[s].x+(v[(s+1)%3].x-v[s].x)*f, y: v[s].y+(v[(s+1)%3].y-v[s].y)*f };
                    }
                    case 'square': {
                        const c = [{x:cx-ex,y:cy-ey},{x:cx+ex,y:cy-ey},{x:cx+ex,y:cy+ey},{x:cx-ex,y:cy+ey}];
                        const cyc = (t*sf) % (Math.PI*2), seg = (Math.PI*2)/4;
                        const s = Math.min(Math.floor(cyc/seg), 3), f = (cyc%seg)/seg;
                        return { x: c[s].x+(c[(s+1)%4].x-c[s].x)*f, y: c[s].y+(c[(s+1)%4].y-c[s].y)*f };
                    }
                    case 'spiral': {
                        const pf = sf*0.25;
                        const s  = Math.abs(Math.asin(Math.sin(t*pf))/(Math.PI/2));
                        const p = 0.09524, q = 0.90476;
                        const rNorm = (-p + Math.sqrt(p*p + 4*q*s)) / (2*q);
                        const rad = r*0.05 + r*0.95*rNorm;
                        const angle = rNorm * Math.PI * 6;
                        return { x: cx + rad*Math.cos(angle), y: cy + rad*Math.sin(angle) };
                    }
                    case 'spring': {
                        return { x: cx + ex*Math.cos(t*sf*0.55), y: cy + ey*Math.sin(t*sf*5.5) };
                    }
                    case 'bee_h': {
                        const N = 3, loopR = ex/(N+1);
                        const fSlow = sf*0.3, fFast = fSlow*N*2;
                        const tri = Math.asin(Math.sin(t*fSlow))/(Math.PI/2);
                        const cX = cx + (ex-loopR)*tri;
                        return { x: cX + loopR*Math.cos(t*fFast), y: cy + loopR*Math.sin(t*fFast) };
                    }
                    case 'bee_v': {
                        const N = 3, loopR = ey/(N+1);
                        const fSlow = sf*0.3, fFast = fSlow*N*2;
                        const tri = Math.asin(Math.sin(t*fSlow))/(Math.PI/2);
                        const cY = cy + (ey-loopR)*tri;
                        return { x: cx + loopR*Math.cos(t*fFast), y: cY + loopR*Math.sin(t*fFast) };
                    }
                    case 'wave_h': {
                        const tri = Math.asin(Math.sin(t*sf))/(Math.PI/2);
                        const arch = Math.cos(tri*Math.PI/2);
                        return { x: cx + ex*tri, y: cy + ey*0.88*(1 - 2*arch) };
                    }
                    case 'wave_h_inv': {
                        const tri = Math.asin(Math.sin(t*sf))/(Math.PI/2);
                        const arch = Math.cos(tri*Math.PI/2);
                        return { x: cx + ex*tri, y: cy - ey*0.88*(1 - 2*arch) };
                    }
                    // zigzag, saccade, particles — posición manejada por estado
                    case 'zigzag':    return { x: this.zz.x   || cx, y: this.zz.y   || cy };
                    case 'saccade':   return { x: this.sc.cur?.x ?? cx, y: this.sc.cur?.y ?? cy };
                    case 'particles': return this.pt.items[0] ?? { x: cx, y: cy };
                    default:          return { x: cx, y: cy };
                }
            },

            // ── Ghost path ────────────────────────────────────────────────────
            drawGhostPath(ctx, w, h, cfg) {
                // Tipos basados en estado no muestran trayectoria fantasma
                if (['saccade', 'zigzag', 'particles'].includes(cfg.exerciseType)) return;

                const sf = this.sf(cfg);

                const isBee    = ['bee_h', 'bee_v'].includes(cfg.exerciseType);
                const isSpiral = cfg.exerciseType === 'spiral';
                const period = isBee
                    ? (Math.PI * 2) / Math.max(0.01, sf * 0.3)   // periodo fSlow=0.3
                    : isSpiral
                        ? (Math.PI * 2) / Math.max(0.01, sf * 0.25) // periodo pf=0.25
                        : (Math.PI * 2) / Math.max(0.01, sf);
                const steps = isBee ? 800 : isSpiral ? 600 : 120;

                ctx.save();
                ctx.beginPath();
                for (let i = 0; i <= steps; i++) {
                    const p = this.computePos(w, h, cfg, (i / steps) * period);
                    i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y);
                }
                ctx.strokeStyle = cfg.color + '28';
                ctx.lineWidth = 1.5;
                ctx.stroke();
                ctx.restore();
            },

            // ── Stimulus ──────────────────────────────────────────────────────
            drawStimulus(ctx, x, y, cfg) {
                const s = cfg.size, color = cfg.color;
                ctx.save();

                // Halo
                const grd = ctx.createRadialGradient(x, y, 0, x, y, s * 2.5);
                grd.addColorStop(0, color + '55');
                grd.addColorStop(1, 'transparent');
                ctx.beginPath();
                ctx.arc(x, y, s * 2.5, 0, Math.PI * 2);
                ctx.fillStyle = grd;
                ctx.fill();

                switch (cfg.stimulusType) {
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
                            const a   = i * Math.PI / 5 - Math.PI / 2;
                            const rad = i % 2 === 0 ? s : s * .4;
                            i === 0 ? ctx.moveTo(x+rad*Math.cos(a), y+rad*Math.sin(a))
                                    : ctx.lineTo(x+rad*Math.cos(a), y+rad*Math.sin(a));
                        }
                        ctx.closePath();
                        ctx.fillStyle = color;
                        ctx.fill();
                        break;
                    case 'cross': {
                        const t2 = Math.max(2, s * .28);
                        ctx.fillStyle = color;
                        ctx.fillRect(x-s, y-t2, s*2, t2*2);
                        ctx.fillRect(x-t2, y-s, t2*2, s*2);
                        break;
                    }
                    case 'emoji':
                        ctx.font = `${s * 2.2}px Arial`;
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(cfg.emoji, x, y);
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
                const w = canvas.width, h = canvas.height;

                // Fondo
                ctx.fillStyle = '#0f172a';
                ctx.fillRect(0, 0, w, h);

                // Guías
                ctx.save();
                ctx.strokeStyle = 'rgba(255,255,255,0.04)';
                ctx.lineWidth = 1;
                ctx.setLineDash([4, 6]);
                ctx.beginPath();
                ctx.moveTo(w/2, 0); ctx.lineTo(w/2, h);
                ctx.moveTo(0, h/2); ctx.lineTo(w, h/2);
                ctx.stroke();
                ctx.setLineDash([]);
                ctx.restore();

                // Trayectoria fantasma
                this.drawGhostPath(ctx, w, h, cfg);

                // Estímulo(s)
                if (cfg.exerciseType === 'particles') {
                    if (!this.pt.init) this.initParticles(w, h, cfg);
                    for (const p of this.pt.items) this.drawStimulus(ctx, p.x, p.y, cfg);
                } else {
                    const pos = this.computePos(w, h, cfg, this.t);
                    this.drawStimulus(ctx, pos.x, pos.y, cfg);
                }
            },

            // ── Lifecycle ─────────────────────────────────────────────────────
            resetState() {
                this.zz = { x:0, y:0, vx:0, vy:0, init:false };
                this.sc = { cur:{x:0,y:0}, nxt:{x:0,y:0}, hold:0, init:false };
                this.pt = { items:[], init:false };
                this.t  = 0;
                this.lastTs = null;
            },

            tick(ts) {
                if (!this.running) { this.rafId = null; return; }
                const canvas = this.$refs.canvas;
                if (!canvas) { this.rafId = null; return; }

                const dt = this.lastTs !== null ? Math.min((ts - this.lastTs) / 1000, 0.1) : 0;
                this.lastTs = ts;
                this.t += dt;

                const cfg = this.getConfig();
                const w = canvas.width, h = canvas.height;

                this.updateZigzag(dt, w, h, cfg);
                this.updateSaccade(dt, w, h, cfg);
                this.updateParticles(dt, w, h, cfg);

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
                    const w = this.$el.clientWidth  || 800;
                    const h = this.$el.clientHeight || 560;
                    if (w > 0 && canvas.width !== w) {
                        canvas.width  = w;
                        canvas.height = h;
                        if (!this.running) this.drawFrame();
                    }
                };
                new ResizeObserver(sync).observe(this.$el);
                sync();
                setTimeout(sync, 100);
                setTimeout(sync, 300);
                setTimeout(() => { sync(); this.start(); }, 600);
            },
        };
    };
})();
</script>
