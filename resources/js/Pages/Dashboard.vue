<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    Clock,
    Eye,
    LayoutDashboard,
    Zap,
} from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import CardFooter from '@/Components/ui/CardFooter.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Separator from '@/Components/ui/Separator.vue';

const props = defineProps({
    plan:          { type: Object,  default: null },
    ejercicios:    { type: Array,   default: () => [] },
    porcentaje:    { type: Number,  default: 0 },
    proximaSesion: { type: Object,  default: null },
});

const page     = usePage();
const user     = computed(() => page.props.auth.user);
const firstName = computed(() => user.value?.name?.split(' ')[0] ?? 'Usuario');

// ─── Exercise type metadata ───────────────────────────────────────────────────
const tipoIconos = {
    circular:     '⭕',
    circular_ccw: '🔄',
    horizontal:   '↔',
    vertical:     '↕',
    diagonal:     '↗',
    diagonal_tr:  '↘',
    triangular:   '🔺',
    square:       '⬛',
    figure8:      '∞',
    figure8_ccw:  '∞',
    figure8_v:    '∞',
    pendulum:     '🎷',
    spiral:       '🌀',
    zigzag:       '⚡',
    saccade:      '👁',
};

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
    pendulum:     'Péndulo',
    spiral:       'Espiral',
    zigzag:       'Zigzag',
    saccade:      'Sacádico',
};

const completadosCount = computed(() =>
    props.ejercicios.filter(e => e.completado).length
);

// El botón está habilitado solo si hay plan y la sesión está disponible ahora
const puedeComenzar = computed(() => {
    if (!props.plan) return false;
    if (!props.proximaSesion) return true;          // sin configuración de horario → siempre
    return props.proximaSesion.disponible === true;
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <LayoutDashboard class="w-5 h-5 text-muted-foreground" />
                <h1 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Dashboard
                </h1>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Bienvenida -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight">
                            ¡Hola, {{ firstName }}! 👋
                        </h2>
                        <p class="text-muted-foreground mt-1">
                            Bienvenido a tu centro de terapia visual. ¿Listo para entrenar hoy?
                        </p>
                    </div>
                    <Button
                        v-if="plan"
                        as-child
                        size="lg"
                        :disabled="!puedeComenzar"
                        :class="!puedeComenzar ? 'opacity-50 pointer-events-none' : ''"
                    >
                        <Link :href="puedeComenzar ? route('exercises.index') : '#'" class="gap-2">
                            <Zap class="w-4 h-4" />
                            Comenzar ejercicio
                        </Link>
                    </Button>
                </div>

                <!-- Sin plan asignado -->
                <div v-if="!plan" class="text-center py-16 space-y-3">
                    <div class="text-5xl">📋</div>
                    <h3 class="text-lg font-semibold">Sin plan asignado</h3>
                    <p class="text-muted-foreground text-sm max-w-sm mx-auto">
                        Aún no tienes un plan de ejercicios. Consulta con tu terapeuta para que te asigne uno.
                    </p>
                </div>

                <!-- Plan de ejercicios -->
                <template v-else>

                    <!-- Progreso general -->
                    <Card>
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">{{ plan.nombre }}</CardTitle>
                                <span class="text-2xl font-bold text-primary">{{ porcentaje }}%</span>
                            </div>
                            <CardDescription>
                                {{ completadosCount }} de {{ ejercicios.length }} ejercicio{{ ejercicios.length !== 1 ? 's' : '' }} completado{{ completadosCount !== 1 ? 's' : '' }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="w-full bg-muted rounded-full h-2.5">
                                <div
                                    class="bg-primary h-2.5 rounded-full transition-all duration-500"
                                    :style="{ width: porcentaje + '%' }"
                                />
                            </div>

                            <!-- Próxima sesión -->
                            <div
                                v-if="proximaSesion"
                                class="flex items-center gap-2 text-sm rounded-lg px-3 py-2"
                                :class="{
                                    'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300': proximaSesion.tipo === 'disponible',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300': proximaSesion.tipo === 'espera',
                                    'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': proximaSesion.tipo === 'proximo_dia',
                                }"
                            >
                                <Clock class="w-4 h-4 shrink-0" />
                                <span>
                                    <span class="font-medium">Próxima sesión:</span>
                                    {{ proximaSesion.mensaje }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Separator />

                    <!-- Ejercicios del plan -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold">Tus ejercicios</h3>
                                <p class="text-sm text-muted-foreground">
                                    Selecciona un ejercicio para comenzar tu sesión.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <Card
                                v-for="ex in ejercicios"
                                :key="ex.id"
                                class="group hover:shadow-md transition-shadow"
                                :class="ex.completado ? 'opacity-75' : ''"
                            >
                                <CardHeader class="pb-2">
                                    <div class="flex items-start justify-between">
                                        <span class="text-3xl leading-none">
                                            {{ ex.tipo_estimulo === 'emoji' && ex.emoji_estimulo ? ex.emoji_estimulo : (tipoIconos[ex.tipo_ejercicio] ?? '👁') }}
                                        </span>
                                        <Badge
                                            v-if="ex.completado"
                                            variant="secondary"
                                            class="text-xs gap-1 text-green-700 bg-green-100 dark:bg-green-900 dark:text-green-300"
                                        >
                                            <CheckCircle2 class="w-3 h-3" />
                                            Visto
                                        </Badge>
                                    </div>
                                    <CardTitle class="text-base mt-2">
                                        {{ tipoNombres[ex.tipo_ejercicio] ?? ex.tipo_ejercicio }}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="pb-3">
                                    <CardDescription class="text-xs space-y-0.5">
                                        <div>Duración: {{ ex.duracion > 0 ? ex.duracion + 's' : 'Sin límite' }}</div>
                                        <div>Velocidad: {{ ex.velocidad }}/10 · Tamaño: {{ ex.tamano }}</div>
                                    </CardDescription>
                                </CardContent>
                                <CardFooter v-if="!ex.completado">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        as-child
                                        class="w-full bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground transition-colors"
                                    >
                                        <Link
                                            :href="route('exercises.index', { start_from_id: ex.id })"
                                            class="gap-1 justify-center"
                                        >
                                            <Eye class="w-4 h-4" />
                                            Practicar
                                        </Link>
                                    </Button>
                                </CardFooter>
                            </Card>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
