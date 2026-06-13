<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ejercicios Visuales — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="/img/logo1.png">
    @vite(['resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-950 overflow-hidden">

{{-- Formulario oculto para enviar la calificación al terminar --}}
<form id="calificacion-form" method="POST" action="{{ route('sesion.completada') }}" style="display:none">
    @csrf
    <input type="hidden" id="cal-input"               name="calificacion">
    <input type="hidden" id="obs-input"               name="observaciones">
    <input type="hidden" id="ardio-ojo-input"         name="ardio_ojo">
    <input type="hidden" id="mas-ejercicios-input"    name="mas_ejercicios">
    <input type="hidden" id="siguio-todos-input"      name="siguio_todos_objetos">
    <input type="hidden" id="ejercicio-no-siguio-input" name="ejercicio_no_siguio">
    <input type="hidden" id="orden-objetos-input"     name="orden_objetos">
    <input type="hidden" id="cansancio-vista-input"   name="cansancio_vista">
</form>

<div
    x-data="exerciseSession({{ Js::from($planItems) }})"
    :class="['fixed inset-0 z-50 bg-gray-950 flex flex-col transition-transform duration-300', isInverted ? 'rotate-180' : '']"
>
    <!-- ── Top bar (oculto durante rating) ──────────────────────────────── -->
    <div
        x-show="pageState !== 'rating'"
        class="flex items-center justify-between px-5 py-3 border-b border-white/10 shrink-0"
    >
        <!-- Nombre del ejercicio / estado -->
        <div class="flex items-center gap-2">
            <span class="text-lg">👁️</span>
            <span class="text-sm font-semibold text-white/80">
                <template x-if="pageState === 'exercising'">
                    <span x-text="exerciseName"></span>
                </template>
                <template x-if="pageState === 'resting'">
                    <span>Descanso</span>
                </template>
                <template x-if="pageState === 'idle'">
                    <span>Terapia Visual</span>
                </template>
            </span>
        </div>

        <!-- Progreso + controles -->
        <div class="flex items-center gap-3">
            <!-- Controles de ejercicio (solo durante ejercicio) -->
            <template x-if="pageState === 'exercising'">
                <div class="flex items-center gap-1.5">
                    <!-- Anterior -->
                    <button
                        @click="prevExercise()"
                        :disabled="currentIndex === 0"
                        class="p-1.5 rounded-lg border border-white/20 text-white/60 hover:bg-white/10 transition-colors disabled:opacity-25 disabled:cursor-not-allowed"
                        title="Ejercicio anterior">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9.195 18.44c1.25.714 2.805-.189 2.805-1.629v-2.34l6.945 3.968c1.25.715 2.805-.188 2.805-1.628V8.69c0-1.44-1.555-2.343-2.805-1.628L12 11.03v-2.34c0-1.44-1.554-2.343-2.805-1.628l-7.108 4.061c-1.26.72-1.26 2.536 0 3.256l7.108 4.061z" />
                        </svg>
                    </button>

                    <!-- Reiniciar -->
                    <button
                        @click="restartExercise()"
                        class="p-1.5 rounded-lg border border-white/20 text-white/60 hover:bg-white/10 transition-colors"
                        title="Reiniciar ejercicio">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </button>

                    <!-- Separador -->
                    <span class="w-px h-4 bg-white/10"></span>

                    <!-- Pausa/Reanudar -->
                    <button @click="togglePause()"
                        class="p-1.5 rounded-lg border border-white/20 text-white/60 hover:bg-white/10 transition-colors">
                        <template x-if="engineState === 'running' || engineState === 'countdown'">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M6.75 5.25a.75.75 0 01.75-.75H9a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H7.5a.75.75 0 01-.75-.75V5.25zm7.5 0A.75.75 0 0115 4.5h1.5a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H15a.75.75 0 01-.75-.75V5.25z" clip-rule="evenodd" />
                            </svg>
                        </template>
                        <template x-if="engineState === 'paused'">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z" clip-rule="evenodd" />
                            </svg>
                        </template>
                    </button>

                    <!-- Separador -->
                    <span class="w-px h-4 bg-white/10"></span>

                    <!-- Saltar -->
                    <button
                        @click="skipExercise()"
                        class="p-1.5 rounded-lg border border-white/20 text-white/60 hover:bg-white/10 transition-colors"
                        title="Saltar ejercicio">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5.055 7.06C3.805 6.347 2.25 7.25 2.25 8.69v8.122c0 1.44 1.555 2.343 2.805 1.628L12 14.471v2.34c0 1.44 1.555 2.343 2.805 1.628l7.108-4.061c1.26-.72 1.26-2.536 0-3.256l-7.108-4.061C13.555 6.346 12 7.249 12 8.689v2.34L5.055 7.061z" />
                        </svg>
                    </button>
                </div>
            </template>

            <!-- Dots de progreso -->
            <template x-if="total > 1">
                <div class="flex gap-1.5">
                    <template x-for="(_, i) in planItems" :key="i">
                        <span class="w-1.5 h-1.5 rounded-full transition-colors"
                              :class="i === currentIndex ? 'bg-cyan-400' : (i < currentIndex ? 'bg-cyan-400/30' : 'bg-white/20')">
                        </span>
                    </template>
                </div>
            </template>

            <span class="text-xs font-medium text-white/50 bg-white/10 rounded-full px-2.5 py-0.5"
                  x-text="(currentIndex + 1) + ' / ' + total">
            </span>

            <!-- Separador -->
            <span class="w-px h-4 bg-white/10"></span>

            <!-- Girar 180° -->
            <button @click="toggleInvert()"
                :class="['p-1.5 rounded-lg border transition-colors',
                    isInverted ? 'border-cyan-400/50 bg-cyan-400/10 text-cyan-400' : 'border-white/20 text-white/60 hover:bg-white/10']"
                title="Girar pantalla 180°">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-4.142-3.358-7.5-7.5-7.5S4.5 7.858 4.5 12m15 0l-2.25-2.25M19.5 12l2.25-2.25M4.5 12l2.25 2.25M4.5 12l-2.25 2.25" />
                </svg>
            </button>

            <!-- Pantalla completa -->
            <button @click="toggleFullscreen()"
                class="p-1.5 rounded-lg border border-white/20 text-white/60 hover:bg-white/10 transition-colors"
                title="Pantalla completa">
                <template x-if="!isFullscreen">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                    </svg>
                </template>
                <template x-if="isFullscreen">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" />
                    </svg>
                </template>
            </button>
        </div>
    </div>

    <!-- ── Canvas (ejercicio activo) ──────────────────────────────────────── -->
    <div
        x-show="pageState === 'exercising' || pageState === 'idle'"
        class="flex-1 min-h-0 relative"
    >
        <canvas x-ref="canvas" class="w-full h-full block"></canvas>

        <!-- Cronómetro — esquina inferior izquierda -->
        <div
            x-show="pageState === 'exercising' && (engineState === 'running' || engineState === 'paused' || engineState === 'countdown')"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="absolute bottom-4 left-4 flex flex-col gap-1.5 select-none pointer-events-none"
        >
            <!-- Tiempo elapsed / total -->
            <div class="flex items-center gap-1.5 bg-black/40 backdrop-blur-sm rounded-lg px-2.5 py-1.5 border border-white/10">
                <svg class="w-3 h-3 text-cyan-400/80 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
                </svg>
                <span class="text-xs font-mono font-semibold text-white/90 tabular-nums" x-text="formatTime(elapsed)"></span>
                <span class="text-xs text-white/30">/</span>
                <span class="text-xs font-mono text-white/40 tabular-nums"
                      x-text="exerciseDuration > 0 ? formatTime(exerciseDuration) : '∞'"></span>
            </div>
            <!-- Barra de progreso -->
            <div class="w-full h-0.5 bg-white/10 rounded-full overflow-hidden" x-show="exerciseDuration > 0">
                <div
                    class="h-full bg-cyan-400/70 rounded-full transition-all duration-1000 ease-linear"
                    :style="'width:' + Math.round(timerProgress * 100) + '%'"
                ></div>
            </div>
        </div>
    </div>

    <!-- ── Pantalla de descanso ───────────────────────────────────────────── -->
    <div
        x-show="pageState === 'resting'"
        class="flex-1 flex flex-col items-center justify-center text-center gap-8 px-6"
    >
        <div class="text-7xl">✅</div>
        <div>
            <h3 class="text-3xl font-bold text-white">¡Ejercicio completado!</h3>
            <div class="mt-2 space-y-0.5">
                <p class="text-sm text-white/50">Siguiente ejercicio:</p>
                <p class="text-lg font-semibold text-white/80" x-text="nextExerciseName"></p>
            </div>
        </div>

        <!-- Anillo de cuenta regresiva -->
        <div class="relative w-36 h-36 flex items-center justify-center">
            <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="44" fill="none" stroke="white" stroke-width="5" opacity="0.1" />
                <circle
                    cx="50" cy="50" r="44"
                    fill="none" stroke="#22d3ee" stroke-width="5"
                    stroke-linecap="round"
                    stroke-dasharray="276.5"
                    :stroke-dashoffset="restDashoffset"
                    style="transition: stroke-dashoffset 1s linear;"
                />
            </svg>
            <span class="text-4xl font-bold text-white" x-text="restSeconds"></span>
        </div>

        <div class="flex items-center gap-3">
            <button
                x-show="currentIndex > 0"
                @click="prevExercise()"
                class="px-5 py-2.5 rounded-xl border border-white/20 text-white/70 text-sm font-medium hover:bg-white/10 transition-colors flex items-center gap-1.5"
            >
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9.195 18.44c1.25.714 2.805-.189 2.805-1.629v-2.34l6.945 3.968c1.25.715 2.805-.188 2.805-1.628V8.69c0-1.44-1.555-2.343-2.805-1.628L12 11.03v-2.34c0-1.44-1.554-2.343-2.805-1.628l-7.108 4.061c-1.26.72-1.26 2.536 0 3.256l7.108 4.061z" />
                </svg>
                Anterior
            </button>
            <button
                @click="skipRest()"
                class="px-6 py-2.5 rounded-xl border border-white/20 text-white/70 text-sm font-medium hover:bg-white/10 transition-colors flex items-center gap-1.5"
            >
                Saltar espera
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M5.055 7.06C3.805 6.347 2.25 7.25 2.25 8.69v8.122c0 1.44 1.555 2.343 2.805 1.628L12 14.471v2.34c0 1.44 1.555 2.343 2.805 1.628l7.108-4.061c1.26-.72 1.26-2.536 0-3.256l-7.108-4.061C13.555 6.346 12 7.249 12 8.689v2.34L5.055 7.061z" />
                </svg>
            </button>
        </div>
    </div>

    <!-- ── Pantalla de calificación ───────────────────────────────────────── -->
    <div
        x-show="pageState === 'rating'"
        class="flex-1 overflow-y-auto flex flex-col items-center text-center gap-6 px-6 py-10"
    >
        <div class="text-6xl">🎉</div>
        <div>
            <h3 class="text-2xl font-bold text-white">¡Sesión completada!</h3>
            <p class="text-sm text-white/50 mt-1">Responde estas breves preguntas</p>
        </div>

        <!-- ¿Cómo te fue? -->
        <div class="w-full max-w-sm">
            <p class="text-sm font-medium text-white/60 mb-2 text-left">¿Cómo te fue hoy?</p>
            <div class="flex gap-3 justify-center">
                <button @click="calificacion = 'bueno'"
                    :class="['flex flex-col items-center gap-1.5 px-4 py-4 rounded-2xl border transition-all flex-1 cursor-pointer',
                        calificacion === 'bueno' ? 'bg-green-500/20 ring-2 ring-green-400 border-green-400/30' : 'border-white/15 hover:bg-green-500/10']">
                    <span class="text-3xl">😄</span>
                    <span class="text-xs font-semibold text-green-300">Bien</span>
                </button>
                <button @click="calificacion = 'regular'"
                    :class="['flex flex-col items-center gap-1.5 px-4 py-4 rounded-2xl border transition-all flex-1 cursor-pointer',
                        calificacion === 'regular' ? 'bg-yellow-500/20 ring-2 ring-yellow-400 border-yellow-400/30' : 'border-white/15 hover:bg-yellow-500/10']">
                    <span class="text-3xl">😐</span>
                    <span class="text-xs font-semibold text-yellow-300">Regular</span>
                </button>
                <button @click="calificacion = 'malo'"
                    :class="['flex flex-col items-center gap-1.5 px-4 py-4 rounded-2xl border transition-all flex-1 cursor-pointer',
                        calificacion === 'malo' ? 'bg-red-500/20 ring-2 ring-red-400 border-red-400/30' : 'border-white/15 hover:bg-red-500/10']">
                    <span class="text-3xl">😞</span>
                    <span class="text-xs font-semibold text-red-300">Mal</span>
                </button>
            </div>
        </div>

        <!-- ¿Le ardió el ojo? -->
        <div class="w-full max-w-sm">
            <p class="text-sm font-medium text-white/60 mb-2 text-left">¿Le ardió el ojo?</p>
            <div class="flex gap-3">
                <button @click="ardioOjo = true"
                    :class="['flex-1 py-2.5 rounded-xl border text-sm font-semibold transition-all cursor-pointer',
                        ardioOjo === true ? 'bg-red-500/20 ring-2 ring-red-400 border-red-400/30 text-red-300' : 'border-white/15 text-white/60 hover:bg-white/5']">
                    Sí
                </button>
                <button @click="ardioOjo = false"
                    :class="['flex-1 py-2.5 rounded-xl border text-sm font-semibold transition-all cursor-pointer',
                        ardioOjo === false ? 'bg-green-500/20 ring-2 ring-green-400 border-green-400/30 text-green-300' : 'border-white/15 text-white/60 hover:bg-white/5']">
                    No
                </button>
            </div>
        </div>

        <!-- ¿Sientes que podrías realizar más ejercicios? -->
        <div class="w-full max-w-sm">
            <p class="text-sm font-medium text-white/60 mb-2 text-left">¿Sientes que podrías realizar más ejercicios?</p>
            <div class="flex gap-3">
                <button @click="masEjercicios = true"
                    :class="['flex-1 py-2.5 rounded-xl border text-sm font-semibold transition-all cursor-pointer',
                        masEjercicios === true ? 'bg-cyan-500/20 ring-2 ring-cyan-400 border-cyan-400/30 text-cyan-300' : 'border-white/15 text-white/60 hover:bg-white/5']">
                    Sí
                </button>
                <button @click="masEjercicios = false"
                    :class="['flex-1 py-2.5 rounded-xl border text-sm font-semibold transition-all cursor-pointer',
                        masEjercicios === false ? 'bg-orange-500/20 ring-2 ring-orange-400 border-orange-400/30 text-orange-300' : 'border-white/15 text-white/60 hover:bg-white/5']">
                    No
                </button>
            </div>
        </div>

        <!-- ¿Pudiste seguir todos los objetos? -->
        <div class="w-full max-w-sm">
            <p class="text-sm font-medium text-white/60 mb-2 text-left">¿Pudiste seguir todos los objetos?</p>
            <div class="flex gap-3 mb-3">
                <button @click="siguioTodos = true; ejercicioNoSiguio = null"
                    :class="['flex-1 py-2.5 rounded-xl border text-sm font-semibold transition-all cursor-pointer',
                        siguioTodos === true ? 'bg-cyan-500/20 ring-2 ring-cyan-400 border-cyan-400/30 text-cyan-300' : 'border-white/15 text-white/60 hover:bg-white/5']">
                    Sí
                </button>
                <button @click="siguioTodos = false"
                    :class="['flex-1 py-2.5 rounded-xl border text-sm font-semibold transition-all cursor-pointer',
                        siguioTodos === false ? 'bg-orange-500/20 ring-2 ring-orange-400 border-orange-400/30 text-orange-300' : 'border-white/15 text-white/60 hover:bg-white/5']">
                    No
                </button>
            </div>
            <!-- Selección de ejercicio no seguido -->
            <div x-show="siguioTodos === false" x-transition class="mt-1">
                <p class="text-xs text-white/40 mb-2 text-left">¿Cuál no pudiste seguir?</p>
                <div class="flex flex-col gap-1.5">
                    <template x-for="item in planItems" :key="item.id">
                        <button
                            @click="ejercicioNoSiguio = item.tipo_ejercicio"
                            :class="['w-full px-3 py-2 rounded-lg border text-xs font-medium text-left transition-all cursor-pointer',
                                ejercicioNoSiguio === item.tipo_ejercicio
                                    ? 'bg-orange-500/20 ring-1 ring-orange-400 border-orange-400/30 text-orange-200'
                                    : 'border-white/10 text-white/50 hover:bg-white/5']"
                            x-text="tipoNombres[item.tipo_ejercicio] ?? item.tipo_ejercicio">
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- ¿Qué objetos en orden seguiste con la vista? -->
        <div class="w-full max-w-sm">
            <label class="block text-sm font-medium text-white/60 mb-1.5 text-left">
                ¿Qué objetos en orden seguiste con la vista?
            </label>
            <textarea
                x-model="ordenObjetos"
                rows="2"
                placeholder="Ej: punto, círculo, figura 8…"
                class="w-full rounded-xl border border-white/15 bg-white/5 text-white placeholder-white/30 px-4 py-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-cyan-500"
            ></textarea>
        </div>

        <!-- Cansancio de la vista 0-10 -->
        <div class="w-full max-w-sm">
            <p class="text-sm font-medium text-white/60 mb-2 text-left">Del 0 al 10, ¿qué tan cansada sintió la vista?</p>
            <div class="flex gap-1.5 flex-wrap justify-center">
                <template x-for="n in [0,1,2,3,4,5,6,7,8,9,10]" :key="n">
                    <button
                        @click="cansancioVista = n"
                        :class="['w-9 h-9 rounded-lg border text-sm font-bold transition-all cursor-pointer',
                            cansancioVista === n
                                ? (n <= 3 ? 'bg-green-500/30 ring-2 ring-green-400 border-green-400/30 text-green-300'
                                    : n <= 6 ? 'bg-yellow-500/30 ring-2 ring-yellow-400 border-yellow-400/30 text-yellow-300'
                                    : 'bg-red-500/30 ring-2 ring-red-400 border-red-400/30 text-red-300')
                                : 'border-white/15 text-white/50 hover:bg-white/5']"
                        x-text="n">
                    </button>
                </template>
            </div>
            <div class="flex justify-between mt-1 px-0.5">
                <span class="text-xs text-white/30">Sin cansancio</span>
                <span class="text-xs text-white/30">Muy cansada</span>
            </div>
        </div>

        <!-- Observaciones -->
        <div class="w-full max-w-sm">
            <label class="block text-sm font-medium text-white/60 mb-1.5 text-left">
                Observaciones (opcional)
            </label>
            <textarea
                x-model="observaciones"
                rows="3"
                placeholder="¿Algo que quieras comentar sobre la sesión?"
                class="w-full rounded-xl border border-white/15 bg-white/5 text-white placeholder-white/30 px-4 py-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-cyan-500"
            ></textarea>
        </div>

        <button
            @click="enviarCalificacion()"
            class="w-full max-w-sm py-3.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-gray-950 font-semibold text-sm transition-colors"
        >
            Finalizar y volver al inicio
        </button>

        <div class="h-4"></div>
    </div>
</div>

</body>
</html>
