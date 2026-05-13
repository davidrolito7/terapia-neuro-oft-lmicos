<x-layouts.guest>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold text-white">Recuperar contraseña</h1>
        <p class="text-sm text-white/60 mt-2 leading-relaxed">
            Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-500/10 border border-green-400/30 px-4 py-3">
            <p class="text-sm text-green-300">{{ session('status') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-500/10 border border-red-400/30 px-4 py-3">
            <p class="text-sm text-red-300">{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-6">
            <label for="email" class="block text-sm font-medium text-white/80 mb-1.5">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-xl border border-white/20 bg-white/10 text-white placeholder-white/30 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 @error('email') border-red-400 @enderror"
                placeholder="tu@correo.com" />
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-gray-950 font-semibold text-sm transition-colors">
            Enviar enlace
        </button>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm text-white/60 hover:text-white transition-colors">
                ← Volver al inicio de sesión
            </a>
        </div>
    </form>
</x-layouts.guest>
