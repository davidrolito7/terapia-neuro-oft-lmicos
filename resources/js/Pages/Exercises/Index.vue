<script setup>
import { provide, reactive } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ExerciseCanvas from '@/Components/Exercises/ExerciseCanvas.vue';
import ExerciseControls from '@/Components/Exercises/ExerciseControls.vue';
import { useExerciseEngine } from '@/composables/useExerciseEngine';

// ─── Shared exercise configuration ───────────────────────────────────────────
const config = reactive({
    exerciseType:  'circular',
    stimulusType:  'dot',
    emoji:         '👁️',
    speed:         5,
    size:          20,
    color:         '#22d3ee',
    delay:         3,
    duration:      60,
});

// ─── Engine (canvas ref lives here, canvas component will bind to it) ─────────
const engine = useExerciseEngine(config);

// Provide to child components
provide('exercise', { config, engine });
</script>

<template>
    <Head title="Ejercicios Visuales" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <span class="text-2xl">👁️</span>
                <div>
                    <h1 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Terapia Visual
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Ejercicios de seguimiento ocular
                    </p>
                </div>
            </div>
        </template>

        <!-- Main layout: controls (left) + canvas (right) -->
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-6" style="min-height: 600px;">

                    <!-- Controls panel -->
                    <aside class="w-full lg:w-80 xl:w-96 shrink-0">
                        <ExerciseControls />
                    </aside>

                    <!-- Canvas area (fills remaining space) -->
                    <main class="flex-1 min-h-[420px] lg:min-h-0">
                        <ExerciseCanvas />
                    </main>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
