<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    Clock,
    Eye,
    Flame,
    LayoutDashboard,
    Target,
    Zap,
} from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';

const page = usePage();
const user = computed(() => page.props.auth.user);

const firstName = computed(() => user.value?.name?.split(' ')[0] ?? 'Usuario');

// ─── Datos de ejemplo ─────────────────────────────────────────────────────────
// En el futuro estos vendrán del servidor vía Inertia props
const stats = [
    {
        title: 'Sesiones hoy',
        value: '0',
        description: 'Completa tu primera sesión',
        icon: Activity,
        badge: null,
    },
    {
        title: 'Tiempo entrenado',
        value: '0 min',
        description: 'Objetivo diario: 15 min',
        icon: Clock,
        badge: null,
    },
    {
        title: 'Racha actual',
        value: '0 días',
        description: '¡Empieza hoy!',
        icon: Flame,
        badge: null,
    },
    {
        title: 'Ejercicios disponibles',
        value: '7',
        description: 'Tipos de seguimiento ocular',
        icon: Target,
        badge: { label: 'Nuevo', variant: 'default' },
    },
];

const exercises = [
    {
        type: 'circular',
        icon: '⭕',
        title: 'Circular',
        description: 'Seguimiento de un punto en trayectoria circular.',
        level: 'Básico',
    },
    {
        type: 'triangular',
        icon: '🔺',
        title: 'Triangular',
        description: 'El punto recorre los vértices de un triángulo.',
        level: 'Básico',
    },
    {
        type: 'vertical',
        icon: '↕',
        title: 'Vertical',
        description: 'Movimiento de arriba a abajo y viceversa.',
        level: 'Básico',
    },
    {
        type: 'horizontal',
        icon: '↔',
        title: 'Horizontal',
        description: 'Movimiento de lado a lado.',
        level: 'Básico',
    },
    {
        type: 'diagonal',
        icon: '↗',
        title: 'Diagonal',
        description: 'Seguimiento en trayectoria diagonal.',
        level: 'Intermedio',
    },
    {
        type: 'figure8',
        icon: '∞',
        title: 'Figura 8',
        description: 'Patrón Lissajous en forma de infinito.',
        level: 'Intermedio',
    },
    {
        type: 'saccade',
        icon: '👁',
        title: 'Sacádico',
        description: 'Saltos rápidos entre posiciones aleatorias.',
        level: 'Avanzado',
    },
];

const levelVariant = { Básico: 'secondary', Intermedio: 'default', Avanzado: 'destructive' };
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
                    <Button as-child size="lg">
                        <Link :href="route('exercises.index')" class="gap-2">
                            <Zap class="w-4 h-4" />
                            Comenzar ejercicio
                        </Link>
                    </Button>
                </div>

                <!-- Stats -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="stat in stats" :key="stat.title">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                {{ stat.title }}
                            </CardTitle>
                            <component :is="stat.icon" class="w-4 h-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-end gap-2">
                                <p class="text-2xl font-bold">{{ stat.value }}</p>
                                <Badge v-if="stat.badge" :variant="stat.badge.variant" class="mb-0.5">
                                    {{ stat.badge.label }}
                                </Badge>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">{{ stat.description }}</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Ejercicios disponibles -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Ejercicios disponibles</h3>
                            <p class="text-sm text-muted-foreground">
                                Selecciona un tipo de ejercicio para comenzar tu sesión.
                            </p>
                        </div>
                        <Button variant="ghost" as-child>
                            <Link :href="route('exercises.index')" class="gap-1">
                                Ver todos
                                <ArrowRight class="w-4 h-4" />
                            </Link>
                        </Button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Card
                            v-for="ex in exercises"
                            :key="ex.type"
                            class="group hover:shadow-md transition-shadow cursor-pointer"
                        >
                            <CardHeader class="pb-2">
                                <div class="flex items-start justify-between">
                                    <span class="text-3xl leading-none">{{ ex.icon }}</span>
                                    <Badge :variant="levelVariant[ex.level]" class="text-xs">
                                        {{ ex.level }}
                                    </Badge>
                                </div>
                                <CardTitle class="text-base mt-2">{{ ex.title }}</CardTitle>
                            </CardHeader>
                            <CardContent class="pb-3">
                                <CardDescription>{{ ex.description }}</CardDescription>
                            </CardContent>
                            <CardFooter>
                                <Button variant="ghost" size="sm" as-child class="w-full group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                                    <Link :href="route('exercises.index')" class="gap-1 justify-center">
                                        <Eye class="w-4 h-4" />
                                        Practicar
                                    </Link>
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                </div>

                <Separator />

                <!-- Cómo usar -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">¿Cómo funciona?</h3>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <Card class="border-dashed">
                            <CardContent class="pt-6 text-center space-y-2">
                                <div class="text-4xl">1️⃣</div>
                                <h4 class="font-semibold">Elige el ejercicio</h4>
                                <p class="text-sm text-muted-foreground">
                                    Selecciona el tipo de trayectoria y estímulo que prefieras.
                                </p>
                            </CardContent>
                        </Card>
                        <Card class="border-dashed">
                            <CardContent class="pt-6 text-center space-y-2">
                                <div class="text-4xl">2️⃣</div>
                                <h4 class="font-semibold">Ajusta la configuración</h4>
                                <p class="text-sm text-muted-foreground">
                                    Controla velocidad, tamaño, color y duración de la sesión.
                                </p>
                            </CardContent>
                        </Card>
                        <Card class="border-dashed">
                            <CardContent class="pt-6 text-center space-y-2">
                                <div class="text-4xl">3️⃣</div>
                                <h4 class="font-semibold">Sigue el estímulo</h4>
                                <p class="text-sm text-muted-foreground">
                                    Mantén la vista en el punto en movimiento durante toda la sesión.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
