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
// idle → exercising → resting → exercising → … → done
const pageState    = ref('idle');
const currentIndex = ref(0);
const restSeconds  = ref(0);
let   restTimer    = null;

const currentItem  = computed(() => props.planItems[currentIndex.value] ?? null);
const hasNext      = computed(() => currentIndex.value + 1 < props.planItems.length);
const total        = computed(() => props.planItems.length);

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
    if (!item) { pageState.value = 'done'; return; }

    currentIndex.value = index;
    applyItem(item);
    engine.reset();
    pageState.value = 'exercising';
    engine.start();
}

function volverAlInicio() {
    // Registra el log de sesión completa y redirige al dashboard
    router.post(route('sesion.completada'));
}

function beginRest() {
    if (!hasNext.value) {
        pageState.value = 'done';
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
</script>

<template>
    <Head title="Ejercicios Visuales" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">👁️</span>
                    <div>
                        <h1 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            Terapia Visual
                        </h1>
                        <p v-if="currentItem && pageState === 'exercising'" class="text-sm text-gray-500 dark:text-gray-400">
                            {{ tipoNombres[currentItem.tipo_ejercicio] ?? currentItem.tipo_ejercicio }}
                        </p>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                            Ejercicios de seguimiento ocular
                        </p>
                    </div>
                </div>

                <!-- Progress pill -->
                <div
                    v-if="total > 0 && pageState !== 'done'"
                    class="text-sm font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded-full px-3 py-1 shrink-0"
                >
                    {{ currentIndex + 1 }} / {{ total }}
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                <!-- Sin ejercicios asignados -->
                <div v-if="planItems.length === 0" class="flex flex-col items-center justify-center text-center space-y-4 py-24">
                    <div class="text-5xl">📋</div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Sin ejercicios asignados</h3>
                    <p class="text-sm text-gray-500">Consulta con tu terapeuta para que configure tu plan.</p>
                    <Button as-child variant="outline">
                        <Link :href="route('dashboard')">← Volver al inicio</Link>
                    </Button>
                </div>

                <template v-else>

                    <!-- ── Canvas activo ─────────────────────────────────── -->
                    <div v-show="pageState === 'exercising' || pageState === 'idle'">
                        <ExerciseCanvas :sequence-mode="true" />

                        <!-- Steps dots -->
                        <div v-if="total > 1" class="flex justify-center gap-2 mt-4">
                            <span
                                v-for="(_, i) in planItems"
                                :key="i"
                                class="w-2 h-2 rounded-full transition-colors"
                                :class="i === currentIndex
                                    ? 'bg-primary'
                                    : i < currentIndex ? 'bg-primary/30' : 'bg-gray-300 dark:bg-gray-600'"
                            />
                        </div>
                    </div>

                    <!-- ── Pantalla de descanso ──────────────────────────── -->
                    <div
                        v-if="pageState === 'resting'"
                        class="flex flex-col items-center justify-center text-center space-y-6 py-20"
                    >
                        <div class="text-6xl">✅</div>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                            ¡Ejercicio completado!
                        </h3>

                        <div class="space-y-1">
                            <p class="text-muted-foreground text-sm">Siguiente ejercicio:</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                {{ tipoNombres[planItems[currentIndex + 1]?.tipo_ejercicio] ?? '' }}
                            </p>
                        </div>

                        <!-- Countdown ring -->
                        <div class="relative w-28 h-28 flex items-center justify-center">
                            <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="44" fill="none" stroke="currentColor" stroke-width="6" class="text-gray-200 dark:text-gray-700" />
                                <circle
                                    cx="50" cy="50" r="44"
                                    fill="none" stroke="currentColor" stroke-width="6"
                                    class="text-primary transition-all duration-1000"
                                    stroke-linecap="round"
                                    :stroke-dasharray="276.5"
                                    :stroke-dashoffset="276.5 * (1 - restSeconds / (currentItem?.descanso_segundos ?? 30))"
                                />
                            </svg>
                            <span class="text-3xl font-bold text-gray-800 dark:text-gray-200">{{ restSeconds }}</span>
                        </div>

                        <Button @click="skipRest" variant="outline" size="lg" class="gap-2">
                            Saltar espera →
                        </Button>
                    </div>

                    <!-- ── Pantalla de fin ───────────────────────────────── -->
                    <div
                        v-if="pageState === 'done'"
                        class="flex flex-col items-center justify-center text-center space-y-6 py-20"
                    >
                        <div class="text-6xl">🎉</div>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                            ¡Plan completado!
                        </h3>
                        <p class="text-muted-foreground max-w-xs">
                            Has terminado todos los ejercicios de hoy. ¡Excelente trabajo!
                        </p>
                        <Button size="lg" @click="volverAlInicio">
                            ← Volver al inicio
                        </Button>
                    </div>

                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
