@php
$tipoIconos = [
    'circular'     => '⭕',
    'circular_ccw' => '🔄',
    'horizontal'   => '↔',
    'vertical'     => '↕',
    'vertical_rev' => '↕',
    'diagonal'     => '↗',
    'diagonal_tr'  => '↘',
    'triangular'   => '🔺',
    'square'       => '⬛',
    'figure8'      => '∞',
    'figure8_ccw'  => '∞',
    'figure8_v'    => '∞',
    'spiral'       => '🌀',
    'zigzag'       => '⚡',
    'saccade'      => '👁',
    'spring'       => '〰',
    'particles'    => '✦',
    'bee_h'        => '🐝',
    'bee_v'        => '🐝',
    'wave_h'       => '〜',
    'wave_h_inv'   => '〜',
    'pentagon'     => '⬠',
    'hexagon'      => '⬡',
    'arrow_bi'     => '↔',
    'cruz'         => '✚',
    'equis'        => '✖',
];

$tipoNombres = [
    'circular'     => 'Circular',
    'circular_ccw' => 'Circular inverso',
    'horizontal'   => 'Horizontal',
    'vertical'     => 'Vertical',
    'vertical_rev' => 'Vertical inverso',
    'diagonal'     => 'Diagonal ↖↘',
    'diagonal_tr'  => 'Diagonal ↗↙',
    'triangular'   => 'Triangular',
    'square'       => 'Cuadrado',
    'figure8'      => 'Figura 8',
    'figure8_ccw'  => 'Figura 8 inverso',
    'figure8_v'    => 'Figura 8 vertical',
    'spiral'       => 'Espiral',
    'zigzag'       => 'Zigzag',
    'saccade'      => 'Sacádico',
    'spring'       => 'Resorte',
    'particles'    => 'Puntos aleatorios',
    'bee_h'        => 'Abeja horizontal',
    'bee_v'        => 'Abeja vertical',
    'wave_h'       => 'Arco de onda',
    'wave_h_inv'   => 'Arco de onda invertido',
    'pentagon'     => 'Pentágono',
    'hexagon'      => 'Hexágono',
    'arrow_bi'     => 'Flecha bidireccional',
    'cruz'         => 'Cruz (+)',
    'equis'        => 'Equis (×)',
];

$firstName     = explode(' ', Auth::user()->name)[0] ?? 'Usuario';
$completados   = collect($ejercicios)->filter(fn($e) => $e['completado'])->count();
$puedeComenzar = !$plan || ($proximaSesion === null) || ($proximaSesion['disponible'] === true);
@endphp

<x-layouts.app>
    <x-slot:title>Dashboard</x-slot:title>

    <x-slot:header>
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            <h1 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Dashboard</h1>
        </div>
    </x-slot:header>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Bienvenida -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        ¡Hola, {{ $firstName }}! 👋
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Bienvenido a tu centro de terapia visual. ¿Listo para entrenar hoy?
                    </p>
                </div>

                @if ($plan)
                    @if ($puedeComenzar)
                        <a href="{{ route('exercises.index') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm transition-colors">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            Comenzar ejercicio
                        </a>
                    @else
                        <span class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-indigo-600/50 text-white/60 font-semibold text-sm cursor-not-allowed">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            Comenzar ejercicio
                        </span>
                    @endif
                @endif
            </div>

            <!-- Sin plan asignado -->
            @if (!$plan)
                <div class="text-center py-16 space-y-3">
                    <div class="text-5xl">📋</div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sin plan asignado</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm max-w-sm mx-auto">
                        Aún no tienes un plan de ejercicios. Consulta con tu terapeuta para que te asigne uno.
                    </p>
                </div>

            @else

                <!-- Plan de ejercicios -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                    <div class="p-6 pb-4">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $plan['nombre'] }}</h3>
                            <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $porcentaje }}%</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $completados }} de {{ count($ejercicios) }} ejercicio{{ count($ejercicios) !== 1 ? 's' : '' }}
                            completado{{ $completados !== 1 ? 's' : '' }}
                        </p>
                    </div>

                    <div class="px-6 pb-4 space-y-3">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500"
                                 style="width: {{ $porcentaje }}%"></div>
                        </div>

                        @if ($proximaSesion)
                            <div class="flex items-center gap-2 text-sm rounded-lg px-3 py-2
                                @if($proximaSesion['tipo'] === 'disponible') bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300
                                @elseif($proximaSesion['tipo'] === 'espera') bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                @else bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                @endif">
                                <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>
                                    <span class="font-medium">Próxima sesión:</span>
                                    {{ $proximaSesion['mensaje'] }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <!-- Ejercicios del plan -->
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tus ejercicios</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Selecciona un ejercicio para comenzar tu sesión.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach ($ejercicios as $ex)
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow {{ $ex['completado'] ? 'opacity-75' : '' }}">
                                <div class="p-6 pb-3">
                                    <div class="flex items-start justify-between">
                                        <span class="text-3xl leading-none">
                                            {{ ($ex['tipo_estimulo'] === 'emoji' && $ex['emoji_estimulo']) ? $ex['emoji_estimulo'] : ($tipoIconos[$ex['tipo_ejercicio']] ?? '👁') }}
                                        </span>
                                        @if ($ex['completado'])
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                </svg>
                                                Visto
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-2">
                                        {{ $tipoNombres[$ex['tipo_ejercicio']] ?? $ex['tipo_ejercicio'] }}
                                    </h4>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 space-y-0.5">
                                        <div>Duración: {{ $ex['duracion'] > 0 ? $ex['duracion'] . 's' : 'Sin límite' }}</div>
                                        <div>Velocidad: {{ $ex['velocidad'] }}/10 · Tamaño: {{ $ex['tamano'] }}</div>
                                    </div>
                                </div>

                                @if (!$ex['completado'])
                                    <div class="px-6 pb-6 pt-0">
                                        <a href="{{ route('exercises.index', ['start_from_id' => $ex['id']]) }}"
                                           class="flex items-center justify-center gap-1.5 w-full py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Practicar
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-layouts.app>
