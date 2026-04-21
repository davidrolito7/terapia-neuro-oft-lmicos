<script setup>
import { computed, onMounted, onUnmounted, provide, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ExerciseCanvas from '@/Components/Exercises/ExerciseCanvas.vue';
import Button from '@/Components/ui/Button.vue';
import { useExerciseEngine } from '@/composables/useExerciseEngine';

const props = defineProps({
    planItems: { type: Array, default: () => [] },
});

// ─── Sequence state ───────────────────────────────────────────────────────────
// idle → exercising → resting → exercising → … → rating
const pageState    = ref('idle');
const currentIndex = ref(0);
const restSeconds  = ref(0);
let   restTimer    = null;

// ─── Rating state ─────────────────────────────────────────────────────────────
const calificacion  = ref(null);   // 'bueno' | 'regular' | 'malo'
const observaciones = ref('');

const currentItem = computed(() => props.planItems[currentIndex.value] ?? null);
const hasNext     = computed(() => currentIndex.value + 1 < props.planItems.length);
const total       = computed(() => props.planItems.length);

// ─── Engine + reactive config ─────────────────────────────────────────────────
const config = reactive({
    exerciseType: 'circular',
    stimulusType: 'dot',
    emoji:        '👁️',
    speed:        5,
    size:         20,
    color:        '#22d3ee',
    delay:        3,
    duration:     60,
});

const engine = useExerciseEngine(config);

// ─── Helpers ──────────────────────────────────────────────────────────────────
function applyItem(item) {
    config.exerciseType = item.tipo_ejercicio;
    config.stimulusType = item.tipo_estimulo ?? 'dot';
    config.emoji        = item.emoji_estimulo ?? '👁️';
    config.speed        = item.velocidad;
    config.size         = item.tamano;
    config.color        = item.color;
    config.duration     = item.duracion;
}

function markComplete(item) {
    if (!item) return;
    router.post(
        route('ejercicios.completar', item.id),
        {},
        { preserveState: true, preserveScroll: true },
    );
}

// ─── Sequence control ─────────────────────────────────────────────────────────
function startAt(index) {
    const item = props.planItems[index];
    if (!item) { mostrarCalificacion(); return; }

    currentIndex.value = index;
    applyItem(item);
    engine.reset();
    pageState.value = 'exercising';
    engine.start();
}

function mostrarCalificacion() {
    pageState.value = 'rating';
}

function enviarCalificacion() {
    router.post(route('sesion.completada'), {
        calificacion:  calificacion.value,
        observaciones: observaciones.value || null,
    });
}

function beginRest() {
    if (!hasNext.value) {
        mostrarCalificacion();
        return;
    }

    const seconds = currentItem.value?.descanso_segundos ?? 30;
    restSeconds.value = seconds;
    pageState.value   = 'resting';

    restTimer = setInterval(() => {
        restSeconds.value -= 1;
        if (restSeconds.value <= 0) {
            clearRest();
            startAt(currentIndex.value + 1);
        }
    }, 1000);
}

function clearRest() {
    clearInterval(restTimer);
    restTimer = null;
}

function skipRest() {
    clearRest();
    startAt(currentIndex.value + 1);
}

// ─── Watch engine: when exercise ends advance sequence ────────────────────────
watch(engine.state, (state) => {
    if (state === 'stopped' && pageState.value === 'exercising') {
        markComplete(currentItem.value);
        beginRest();
    }
});

// ─── Auto-start on mount ──────────────────────────────────────────────────────
onMounted(() => {
    if (props.planItems.length > 0) startAt(0);
});

onUnmounted(() => clearRest());

provide('exercise', { config, engine });

// ─── Exercise type names for display ─────────────────────────────────────────
const tipoNombres = {
    circular:     'Circular',
    circular_ccw: 'Circular inverso',
    horizontal:   'Horizontal',
    vertical:     'Vertical',
    vertical_rev: 'Vertical inverso',
    diagonal:     'Diagonal ↖↘',
    diagonal_tr:  'Diagonal ↗↙',
    triangular:   'Triangular',
    square:       'Cuadrado',
    figure8:      'Figura 8',
    figure8_ccw:  'Figura 8 inverso',
    figure8_v:    'Figura 8 vertical',
    spiral:       'Espiral',
    zigzag:       'Zigzag',
    saccade:      'Sacádico',
    spring:       'Resorte',
    particles:    'Puntos aleatorios',
    bee_h:        'Abeja horizontal',
    bee_v:        'Abeja vertical',
    wave_h:       'Arco de onda',
    wave_h_inv:   'Arco de onda invertido',
};

// Fullscreen overlay is active during exercising / resting / rating
const inSession = computed(() =>
    ['exercising', 'idle', 'resting', 'rating'].includes(pageState.value) && props.planItems.length > 0
);
</script>

<template>
    <Head title="Ejercicios Visuales" />

    <!-- ── Normal layout: only shown when no exercises assigned ───────────── -->
    <AuthenticatedLayout v-if="planItems.length === 0">
        <div class="py-6">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-center text-center space-y-4 py-24">
                    <div class="text-5xl">📋</div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Sin ejercicios asignados</h3>
                    <p class="text-sm text-gray-500">Consulta con tu terapeuta para que configure tu plan.</p>
                    <Button as-child variant="outline">
                        <Link :href="route('dashboard')">← Volver al inicio</Link>
                    </Button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- ── Fullscreen session overlay ─────────────────────────────────────── -->
    <Teleport to="body">
        <div
            v-if="inSession"
            class="fixed inset-0 z-50 bg-gray-950 flex flex-col"
        >
            <!-- ── Top bar ──────────────────────────────────────────────── -->
            <div
                v-if="pageState !== 'rating'"
                class="flex items-center justify-between px-5 py-3 border-b border-white/10 shrink-0"
            >
                <!-- Exercise name -->
                <div class="flex items-center gap-2">
                    <span class="text-lg">👁️</span>
                    <span class="text-sm font-semibold text-white/80">
                        <template v-if="pageState === 'exercising' && currentItem">
                            {{ tipoNombres[currentItem.tipo_ejercicio] ?? currentItem.tipo_ejercicio }}
                        </template>
                        <template v-else-if="pageState === 'resting'">
                            Descanso
                        </template>
                        <template v-else>
                            Terapia Visual
                        </template>
                    </span>
                </div>

                <!-- Progress pill -->
                <div class="flex items-center gap-3">
                    <!-- Step dots -->
                    <div v-if="total > 1" class="flex gap-1.5">
                        <span
                            v-for="(_, i) in planItems"
                            :key="i"
                            class="w-1.5 h-1.5 rounded-full transition-colors"
                            :class="i === currentIndex
                                ? 'bg-cyan-400'
                                : i < currentIndex ? 'bg-cyan-400/30' : 'bg-white/20'"
                        />
                    </div>
                    <span class="text-xs font-medium text-white/50 bg-white/10 rounded-full px-2.5 py-0.5">
                        {{ currentIndex + 1 }} / {{ total }}
                    </span>
                </div>
            </div>

            <!-- ── Canvas area (exercising / idle) ──────────────────────── -->
            <div
                v-show="pageState === 'exercising' || pageState === 'idle'"
                class="flex-1 min-h-0"
            >
                <ExerciseCanvas :sequence-mode="true" :fill-parent="true" />
            </div>

            <!-- ── Rest screen ───────────────────────────────────────────── -->
            <div
                v-if="pageState === 'resting'"
                class="flex-1 flex flex-col items-center justify-center text-center gap-8 px-6"
            >
                <div class="text-7xl">✅</div>
                <div>
                    <h3 class="text-3xl font-bold text-white">¡Ejercicio completado!</h3>
                    <div class="mt-2 space-y-0.5">
                        <p class="text-sm text-white/50">Siguiente ejercicio:</p>
                        <p class="text-lg font-semibold text-white/80">
                            {{ tipoNombres[planItems[currentIndex + 1]?.tipo_ejercicio] ?? '' }}
                        </p>
                    </div>
                </div>

                <!-- Countdown ring -->
                <div class="relative w-36 h-36 flex items-center justify-center">
                    <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="44" fill="none" stroke="white" stroke-width="5" opacity="0.1" />
                        <circle
                            cx="50" cy="50" r="44"
                            fill="none" stroke="#22d3ee" stroke-width="5"
                            stroke-linecap="round"
                            :stroke-dasharray="276.5"
                            :stroke-dashoffset="276.5 * (1 - restSeconds / (currentItem?.descanso_segundos ?? 30))"
                            class="transition-all duration-1000"
                        />
                    </svg>
                    <span class="text-4xl font-bold text-white">{{ restSeconds }}</span>
                </div>

                <button
                    @click="skipRest"
                    class="px-6 py-2.5 rounded-xl border border-white/20 text-white/70 text-sm font-medium hover:bg-white/10 transition-colors"
                >
                    Saltar espera →
                </button>
            </div>

            <!-- ── Rating screen ─────────────────────────────────────────── -->
            <div
                v-if="pageState === 'rating'"
                class="flex-1 flex flex-col items-center justify-center text-center gap-8 px-6 py-10"
            >
                <div class="text-7xl">🎉</div>
                <div>
                    <h3 class="text-3xl font-bold text-white">¡Sesión completada!</h3>
                    <p class="text-sm text-white/50 mt-1">¿Cómo te fue hoy?</p>
                </div>

                <!-- Rating buttons -->
                <div class="flex gap-4 w-full max-w-sm justify-center">
                    <button
                        v-for="opcion in [
                            { valor: 'bueno',   emoji: '😄', label: 'Bien',    sel: 'bg-green-500/20 ring-2 ring-green-400 scale-105',  base: 'border-white/15 hover:bg-green-500/10',  text: 'text-green-300' },
                            { valor: 'regular', emoji: '😐', label: 'Regular', sel: 'bg-yellow-500/20 ring-2 ring-yellow-400 scale-105', base: 'border-white/15 hover:bg-yellow-500/10', text: 'text-yellow-300' },
                            { valor: 'malo',    emoji: '😞', label: 'Mal',     sel: 'bg-red-500/20 ring-2 ring-red-400 scale-105',      base: 'border-white/15 hover:bg-red-500/10',    text: 'text-red-300' },
                        ]"
                        :key="opcion.valor"
                        @click="calificacion = opcion.valor"
                        :class="[
                            'flex flex-col items-center gap-2 px-5 py-5 rounded-2xl border transition-all flex-1 cursor-pointer',
                            calificacion === opcion.valor ? opcion.sel : opcion.base,
                        ]"
                    >
                        <span class="text-4xl">{{ opcion.emoji }}</span>
                        <span :class="['text-sm font-semibold', opcion.text]">{{ opcion.label }}</span>
                    </button>
                </div>

                <!-- Observations -->
                <div class="w-full max-w-sm">
                    <label class="block text-sm font-medium text-white/60 mb-1.5 text-left">
                        Observaciones (opcional)
                    </label>
                    <textarea
                        v-model="observaciones"
                        rows="3"
                        placeholder="¿Algo que quieras comentar sobre la sesión?"
                        class="w-full rounded-xl border border-white/15 bg-white/5 text-white placeholder-white/30 px-4 py-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-cyan-500"
                    />
                </div>

                <button
                    @click="enviarCalificacion"
                    class="w-full max-w-sm py-3.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-gray-950 font-semibold text-sm transition-colors"
                >
                    Finalizar y volver al inicio
                </button>
            </div>
        </div>
    </Teleport>
</template>
