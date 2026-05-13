<x-layouts.guest>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold text-white">Iniciar sesión</h1>
        <p class="text-sm text-white/60 mt-1">Ingresa con tu teléfono y fecha de nacimiento</p>
    </div>

    <!-- Video decorativo -->
    <div class="mb-6 rounded-xl overflow-hidden">
        <video autoplay loop muted playsinline class="w-full rounded-xl opacity-80" style="max-height: 160px; object-fit: cover;">
            <source src="/img/video.mp4" type="video/mp4">
        </video>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-500/10 border border-red-400/30 px-4 py-3">
            <p class="text-sm text-red-300">{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-4">
            <label for="telefono" class="block text-sm font-medium text-white/80 mb-1.5">Teléfono</label>
            <input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}" required autofocus
                class="w-full rounded-xl border border-white/20 bg-white/10 text-white placeholder-white/30 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 @error('telefono') border-red-400 @enderror" />
        </div>

        <div class="mb-6">
            <label for="fecha_nacimiento" class="block text-sm font-medium text-white/80 mb-1.5">Fecha de nacimiento</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required
                class="w-full rounded-xl border border-white/20 bg-white/10 text-white placeholder-white/30 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 @error('fecha_nacimiento') border-red-400 @enderror" />
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-gray-950 font-semibold text-sm transition-colors">
            Entrar
        </button>
    </form>
</x-layouts.guest>
