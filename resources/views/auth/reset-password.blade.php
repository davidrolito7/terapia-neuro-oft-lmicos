<x-layouts.guest>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold text-white">Nueva contraseña</h1>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-500/10 border border-red-400/30 px-4 py-3">
            <p class="text-sm text-red-300">{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-white/80 mb-1.5">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus
                class="w-full rounded-xl border border-white/20 bg-white/10 text-white placeholder-white/30 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 @error('email') border-red-400 @enderror" />
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-white/80 mb-1.5">Nueva contraseña</label>
            <input type="password" id="password" name="password" required
                class="w-full rounded-xl border border-white/20 bg-white/10 text-white placeholder-white/30 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 @error('password') border-red-400 @enderror" />
        </div>

        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-white/80 mb-1.5">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                class="w-full rounded-xl border border-white/20 bg-white/10 text-white placeholder-white/30 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500" />
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-gray-950 font-semibold text-sm transition-colors">
            Restablecer contraseña
        </button>
    </form>
</x-layouts.guest>
