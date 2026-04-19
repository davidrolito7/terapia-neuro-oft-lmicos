<script setup>
</script>

<template>
    <div
        class="relative flex min-h-screen overflow-hidden"
        style="background-image: url('/img/fondo.jpeg'); background-size: cover; background-position: center;"
    >
        <!-- Máscara oscura -->
        <div class="absolute inset-0 bg-black/65 backdrop-blur-[2px]"></div>

        <!-- Layout de tres columnas -->
        <div class="relative z-10 flex w-full items-center justify-between px-8 py-12 gap-6">

            <!-- Columna izquierda — Logo -->
            <div class="hidden lg:flex flex-1 items-center justify-center">
                <div class="overflow-hidden" style="width: 300px; height: 300px;">
                    <img
                        src="/img/logo1.png"
                        alt="Logo"
                        class="w-full h-full object-contain scale-[1.35]"
                    />
                </div>
            </div>

            <!-- Columna central — Formulario -->
            <div class="flex flex-col items-center w-full lg:w-auto lg:flex-none">
                <div class="w-full" style="width: min(100vw - 2rem, 420px);">
                    <div class="rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 shadow-2xl px-8 py-8">
                        <slot />
                    </div>
                </div>
            </div>

            <!-- Columna derecha — Aviso -->
            <div class="hidden lg:flex flex-1 items-center justify-center">
                <div class="max-w-xs rounded-xl border border-amber-400/30 bg-amber-500/10 backdrop-blur-sm px-6 py-5">
                    <div class="flex gap-3">
                        <span class="text-amber-400 text-xl leading-none mt-0.5 shrink-0">⚠️</span>
                        <p class="text-sm text-amber-100/90 leading-relaxed">
                            Este programa está diseñado para el uso exclusivo de pacientes neuro-oftálmicos.
                            El uso indebido de este programa podría lesionar parcialmente a su paciente,
                            favor de seguir las instrucciones de los profesionales a cargo.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
