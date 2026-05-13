<x-layouts.guest>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold text-white">Confirmar contraseña</h1>
        <p class="text-sm text-white/60 mt-2 leading-relaxed">
            Esta es una zona segura. Confirma tu contraseña para continuar.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-500/10 border border-red-400/30 px-4 py-3">
            <p class="text-sm text-red-300">{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-6">
            <label for="password" class="block text-sm font-medium text-white/80 mb-1.5">Contraseña</label>
            <input type="password" id="password" name="password" required autofocus
                class="w-full rounded-xl border border-white/20 bg-white/10 text-white placeholder-white/30 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 @error('password') border-red-400 @enderror" />
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-gray-950 font-semibold text-sm transition-colors">
            Confirmar
        </button>
    </form>
</x-layouts.guest>
