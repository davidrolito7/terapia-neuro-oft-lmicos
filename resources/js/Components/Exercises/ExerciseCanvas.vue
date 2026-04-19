<script setup>
import { computed, inject, onMounted, onUnmounted, ref } from 'vue';
import { Maximize2, Minimize2, Pause, Play, RotateCcw, Square } from 'lucide-vue-next';

const props = defineProps({
    sequenceMode: { type: Boolean, default: false },
});

const { config, engine } = inject('exercise');
const { canvasRef, state, countdownValue, elapsedSeconds, pause, resume, stop, reset, redrawAfterResize } = engine;

// ─── Refs locales del DOM ─────────────────────────────────────────────────────
// Usamos refs locales normales (ref="myCanvas") para evitar cualquier
// ambigüedad con el auto-unwrapping de Vue en templates.
const myCanvas  = ref(null);
const wrapperRef = ref(null);
const isFullscreen = ref(false);

// ─── Fullscreen ───────────────────────────────────────────────────────────────
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        wrapperRef.value?.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
}
function onFullscreenChange() {
    isFullscreen.value = !!document.fullscreenElement;
}

// ─── Canvas sizing ────────────────────────────────────────────────────────────
function syncSize() {
    if (!wrapperRef.value || !myCanvas.value) return;
    const w = wrapperRef.value.clientWidth;
    const h = wrapperRef.value.clientHeight;
    if (w > 0 && h > 0) {
        myCanvas.value.width  = w;
        myCanvas.value.height = h;
        redrawAfterResize();
    }
}

let resizeObserver = null;

onMounted(() => {
    // En onMounted el DOM ya existe → myCanvas.value es el elemento <canvas>
    // Lo asignamos al ref del engine para que pueda dibujar en él.
    canvasRef.value = myCanvas.value;

    // Dar dimensiones iniciales antes de que el usuario pulse Iniciar
    syncSize();

    document.addEventListener('fullscreenchange', onFullscreenChange);

    resizeObserver = new ResizeObserver(() => syncSize());
    if (wrapperRef.value) resizeObserver.observe(wrapperRef.value);
});

onUnmounted(() => {
    canvasRef.value = null;
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    resizeObserver?.disconnect();
});

// ─── Display helpers ──────────────────────────────────────────────────────────
function formatTime(seconds) {
    const s = Math.floor(seconds ?? 0);
    const m = Math.floor(s / 60).toString().padStart(2, '0');
    const sec = (s % 60).toString().padStart(2, '0');
    return `${m}:${sec}`;
}

// En computed (contexto JS) sí se usa .value
const statusInfo = computed(() => {
    const map = {
        idle:      { text: 'En espera',                              dot: 'bg-slate-500' },
        countdown: { text: `Iniciando en ${countdownValue.value}s…`, dot: 'bg-cyan-400 animate-pulse' },
        running:   { text: 'Ejecutando',                             dot: 'bg-green-400 animate-pulse' },
        paused:    { text: 'Pausado',                                dot: 'bg-yellow-400' },
        stopped:   { text: 'Detenido',                               dot: 'bg-red-400' },
    };
    return map[state.value] ?? map.idle;
});

const showTimer = computed(() => state.value === 'running' || state.value === 'paused');
const durationLabel = computed(() => config.duration === 0 ? '∞' : formatTime(config.duration));
</script>

<template>
    <!--
        Altura explícita en el wrapper para que clientHeight > 0 en onMounted.
        El canvas usa absolute inset-0 para llenar exactamente este contenedor.
    -->
    <div
        ref="wrapperRef"
        class="relative w-full rounded-xl overflow-hidden bg-slate-900 border border-slate-700"
        :class="{ '!rounded-none !border-0': isFullscreen }"
        style="height: max(420px, min(60vh, 680px))"
    >
        <!-- Canvas local: ref="myCanvas" (sin ambigüedad de auto-unwrap) -->
        <canvas ref="myCanvas" class="absolute inset-0 w-full h-full block" />

        <!-- Controles flotantes (top-right) -->
        <!-- En template de <script setup> los refs se desenvuelven: state === 'x', NO state.value -->
        <div class="absolute top-3 right-3 flex items-center gap-2 z-10">
            <template v-if="state === 'running'">
                <button class="canvas-btn" title="Pausar" @click="pause()">
                    <Pause class="w-4 h-4" />
                </button>
            </template>
            <template v-else-if="state === 'paused'">
                <button class="canvas-btn" title="Continuar" @click="resume()">
                    <Play class="w-4 h-4" />
                </button>
            </template>
            <template v-if="!sequenceMode && (state === 'running' || state === 'paused')">
                <button class="canvas-btn" title="Detener" @click="stop()">
                    <Square class="w-4 h-4" />
                </button>
                <button class="canvas-btn" title="Reiniciar" @click="reset()">
                    <RotateCcw class="w-4 h-4" />
                </button>
            </template>
            <button
                class="canvas-btn"
                :title="isFullscreen ? 'Salir' : 'Pantalla completa'"
                @click="toggleFullscreen"
            >
                <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
                <Maximize2 v-else class="w-4 h-4" />
            </button>
        </div>

        <!-- Timer (bottom-left) — elapsedSeconds sin .value en template -->
        <div
            v-if="showTimer"
            class="absolute bottom-3 left-3 font-mono text-white/80 text-sm bg-black/50 backdrop-blur-sm rounded-md px-2.5 py-1 z-10"
        >
            {{ formatTime(elapsedSeconds) }}
            <span v-if="config.duration > 0" class="text-white/40"> / {{ durationLabel }}</span>
        </div>

        <!-- Estado (bottom-right) -->
        <div class="absolute bottom-3 right-3 flex items-center gap-1.5 z-10">
            <span class="w-2 h-2 rounded-full" :class="statusInfo.dot" />
            <span class="text-xs text-white/60 bg-black/30 backdrop-blur-sm rounded px-1.5 py-0.5">
                {{ statusInfo.text }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.canvas-btn {
    @apply flex items-center justify-center w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white transition-colors backdrop-blur-sm;
}
</style>
