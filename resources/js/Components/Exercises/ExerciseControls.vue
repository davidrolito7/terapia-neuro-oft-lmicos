<script setup>
import { computed, inject } from 'vue';
import { Pause, Play, RotateCcw, Square } from 'lucide-vue-next';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Label from '@/Components/ui/Label.vue';
import Separator from '@/Components/ui/Separator.vue';
import Select from '@/Components/ui/Select.vue';
import SelectContent from '@/Components/ui/SelectContent.vue';
import SelectItem from '@/Components/ui/SelectItem.vue';
import SelectTrigger from '@/Components/ui/SelectTrigger.vue';
import SelectValue from '@/Components/ui/SelectValue.vue';
import Slider from '@/Components/ui/Slider.vue';

const { config, engine } = inject('exercise');
const { state, elapsedSeconds, start, pause, resume, stop, reset } = engine;

// ─── Opciones ─────────────────────────────────────────────────────────────────
const exerciseOptions = [
    { value: 'circular',     label: '⭕  Circular (horario)' },
    { value: 'circular_ccw', label: '🔄  Circular (antihorario)' },
    { value: 'figure8',      label: '∞  Figura 8' },
    { value: 'figure8_ccw',  label: '∞  Figura 8 (inverso)' },
    { value: 'figure8_v',    label: '∞  Figura 8 vertical' },
    { value: 'horizontal',   label: '↔  Horizontal' },
    { value: 'vertical',     label: '↕  Vertical (abajo)' },
    { value: 'vertical_rev', label: '↕  Vertical (arriba)' },
    { value: 'diagonal',     label: '↗  Diagonal ↖↘' },
    { value: 'diagonal_tr',  label: '↘  Diagonal ↗↙' },
    { value: 'triangular',   label: '🔺  Triangular' },
    { value: 'square',       label: '⬛  Cuadrado' },
    { value: 'pendulum',     label: '🎷  Péndulo' },
    { value: 'spiral',       label: '🌀  Espiral' },
    { value: 'zigzag',       label: '⚡  Zigzag (rebote)' },
    { value: 'saccade',      label: '👁  Sacádico (aleatorio)' },
];

const stimulusOptions = [
    { value: 'dot',   label: '●  Punto' },
    { value: 'ring',  label: '○  Anillo' },
    { value: 'star',  label: '★  Estrella' },
    { value: 'cross', label: '✚  Cruz' },
    { value: 'emoji', label: '😊  Emoji' },
];

const emojiOptions = ['👁️','⭐','🔴','🟡','🟢','🔵','🦋','🌟','❤️','🎯','🐝','🌈'];

// ─── Estado ───────────────────────────────────────────────────────────────────
const canStart  = computed(() => state.value === 'idle'    || state.value === 'stopped');
const canPause  = computed(() => state.value === 'running');
const canResume = computed(() => state.value === 'paused');
const canStop   = computed(() => ['running','paused','countdown'].includes(state.value));
const isActive  = computed(() => ['running','paused','countdown'].includes(state.value));

const badgeVariant = computed(() => ({
    idle: 'outline', countdown: 'default',
    running: 'default', paused: 'outline', stopped: 'destructive',
}[state.value] ?? 'outline'));

const stateLabel = computed(() => ({
    idle: 'En espera', countdown: 'Iniciando…',
    running: 'Ejecutando', paused: 'Pausado', stopped: 'Detenido',
}[state.value] ?? 'En espera'));

const speedLabel = computed(() => {
    if (config.speed <= 2) return 'Muy lento';
    if (config.speed <= 4) return 'Lento';
    if (config.speed <= 6) return 'Medio';
    if (config.speed <= 8) return 'Rápido';
    return 'Muy rápido';
});

function formatTime(s) {
    const sec = Math.floor(s ?? 0);
    return `${Math.floor(sec/60).toString().padStart(2,'0')}:${(sec%60).toString().padStart(2,'0')}`;
}
</script>

<template>
    <div class="flex flex-col gap-4 overflow-y-auto pb-2">

        <!-- Estado -->
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold">Configuración</h2>
            <div class="flex items-center gap-2">
                <Badge :variant="badgeVariant">{{ stateLabel }}</Badge>
                <span v-if="state === 'running' || state === 'paused'"
                      class="text-xs font-mono text-muted-foreground">
                    {{ formatTime(elapsedSeconds) }}
                </span>
            </div>
        </div>

        <!-- Tipo de ejercicio y estímulo -->
        <Card>
            <CardHeader class="pb-3">
                <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wide">
                    Ejercicio
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">

                <div class="space-y-2">
                    <Label>Tipo de ejercicio</Label>
                    <Select v-model="config.exerciseType">
                        <SelectTrigger>
                            <SelectValue placeholder="Seleccionar tipo…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="opt in exerciseOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <Label>Estímulo visual</Label>
                    <Select v-model="config.stimulusType">
                        <SelectTrigger>
                            <SelectValue placeholder="Seleccionar estímulo…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="opt in stimulusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Selector de emoji -->
                <div v-if="config.stimulusType === 'emoji'" class="space-y-2">
                    <Label>Emoji</Label>
                    <div class="grid grid-cols-6 gap-1">
                        <button
                            v-for="emoji in emojiOptions"
                            :key="emoji"
                            type="button"
                            class="text-xl rounded-md p-1 border transition-colors hover:bg-accent"
                            :class="config.emoji === emoji
                                ? 'border-primary bg-primary/10'
                                : 'border-transparent'"
                            @click="config.emoji = emoji"
                        >{{ emoji }}</button>
                    </div>
                </div>

            </CardContent>
        </Card>

        <!-- Velocidad, tamaño y color -->
        <Card>
            <CardHeader class="pb-3">
                <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wide">
                    Movimiento
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-5">

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <Label>Velocidad</Label>
                        <span class="text-sm text-muted-foreground">{{ config.speed }} · {{ speedLabel }}</span>
                    </div>
                    <!-- shadcn-vue Slider: modelValue es un array -->
                    <Slider
                        :model-value="[config.speed]"
                        :min="1" :max="10" :step="1"
                        @update:model-value="config.speed = $event[0]"
                    />
                    <div class="flex justify-between text-xs text-muted-foreground">
                        <span>Lento</span><span>Rápido</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <Label>Tamaño</Label>
                        <span class="text-sm text-muted-foreground">{{ config.size }}px</span>
                    </div>
                    <Slider
                        :model-value="[config.size]"
                        :min="8" :max="60" :step="1"
                        @update:model-value="config.size = $event[0]"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Color del estímulo</Label>
                    <div class="flex items-center gap-3">
                        <input
                            v-model="config.color"
                            type="color"
                            class="h-9 w-12 rounded-md border border-input cursor-pointer p-0.5 bg-background"
                        />
                        <span class="text-sm font-mono text-muted-foreground">{{ config.color }}</span>
                    </div>
                </div>

            </CardContent>
        </Card>

        <!-- Tiempo -->
        <Card>
            <CardHeader class="pb-3">
                <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wide">
                    Tiempo
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-5">

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <Label>Espera antes de iniciar</Label>
                        <span class="text-sm text-muted-foreground">{{ config.delay }}s</span>
                    </div>
                    <Slider
                        :model-value="[config.delay]"
                        :min="0" :max="10" :step="1"
                        @update:model-value="config.delay = $event[0]"
                    />
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <Label>Duración</Label>
                        <span class="text-sm text-muted-foreground">
                            {{ config.duration === 0 ? 'Sin límite' : formatTime(config.duration) }}
                        </span>
                    </div>
                    <Slider
                        :model-value="[config.duration]"
                        :min="0" :max="300" :step="15"
                        @update:model-value="config.duration = $event[0]"
                    />
                    <div class="flex justify-between text-xs text-muted-foreground">
                        <span>Sin límite</span><span>5 min</span>
                    </div>
                </div>

            </CardContent>
        </Card>

        <Separator />

        <!-- Botones de control -->
        <div class="space-y-2">
            <Button v-if="canStart"  class="w-full" @click="start()">
                <Play class="w-4 h-4 mr-2" /> Iniciar ejercicio
            </Button>
            <Button v-else-if="canResume" class="w-full" @click="resume()">
                <Play class="w-4 h-4 mr-2" /> Continuar
            </Button>

            <Button v-if="canPause" variant="secondary" class="w-full" @click="pause()">
                <Pause class="w-4 h-4 mr-2" /> Pausar
            </Button>

            <div v-if="canStop" class="flex gap-2">
                <Button variant="outline" class="flex-1" @click="stop()">
                    <Square class="w-4 h-4 mr-2" /> Detener
                </Button>
                <Button variant="ghost" class="flex-1" @click="reset()">
                    <RotateCcw class="w-4 h-4 mr-2" /> Reiniciar
                </Button>
            </div>

            <Button
                v-if="!isActive && state !== 'idle'"
                variant="ghost" class="w-full"
                @click="reset()"
            >
                <RotateCcw class="w-4 h-4 mr-2" /> Reiniciar
            </Button>
        </div>

    </div>
</template>
