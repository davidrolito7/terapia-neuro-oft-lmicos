<x-layouts.guest>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold text-white">Verificar correo</h1>
        <p class="text-sm text-white/60 mt-2 leading-relaxed">
            Por favor verifica tu correo haciendo clic en el enlace que te enviamos.
            Si no lo recibiste, puedes solicitar uno nuevo.
        </p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-lg bg-green-500/10 border border-green-400/30 px-4 py-3">
            <p class="text-sm text-green-300">
                Se ha enviado un nuevo enlace de verificación a tu correo.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit"
            class="w-full py-3 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-gray-950 font-semibold text-sm transition-colors">
            Reenviar correo de verificación
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit"
            class="w-full py-3 rounded-xl border border-white/20 text-white/70 text-sm font-medium hover:bg-white/10 transition-colors">
            Cerrar sesión
        </button>
    </form>
</x-layouts.guest>
