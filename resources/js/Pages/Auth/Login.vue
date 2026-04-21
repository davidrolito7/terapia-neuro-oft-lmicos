<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    telefono: '',
    fecha_nacimiento: '',
});

const submit = () => {
    form.post(route('login'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <!-- Video -->
        <div class="overflow-hidden rounded-xl mb-6" style="height: 180px;">
            <video
                autoplay
                loop
                muted
                playsinline
                class="w-full object-cover"
                style="margin-top: -5%; height: 110%;"
            >
                <source src="/img/video.mp4" type="video/mp4" />
            </video>
        </div>

        <div class="mb-6 text-center">
            <h1 class="text-xl font-semibold text-white">
                Inicia sesión en tu centro de terapia
            </h1>
            <p class="text-sm text-white/60 mt-1">
                Ingresa con tu número de teléfono y fecha de nacimiento
            </p>
        </div>

        <form @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-white/80">Teléfono</label>
                <TextInput
                    id="telefono"
                    type="tel"
                    class="mt-1 block w-full bg-white/10 border-white/20 text-white placeholder-white/40 focus:border-white/60 focus:ring-white/30"
                    v-model="form.telefono"
                    required
                    autofocus
                    placeholder="Ej. 5512345678"
                />
                <InputError class="mt-2" :message="form.errors.telefono" />
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-white/80">Fecha de nacimiento</label>
                <TextInput
                    id="fecha_nacimiento"
                    type="date"
                    class="mt-1 block w-full bg-white/10 border-white/20 text-white focus:border-white/60 focus:ring-white/30"
                    v-model="form.fecha_nacimiento"
                    required
                />
                <InputError class="mt-2" :message="form.errors.fecha_nacimiento" />
            </div>

            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Iniciar sesión
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
