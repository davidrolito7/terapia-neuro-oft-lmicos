<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Información del perfil</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Actualiza tu nombre y correo electrónico.
        </p>
    </header>

    @if (session('status') === 'profile-updated')
        <div class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 dark:bg-green-900/20 dark:border-green-800">
            <p class="text-sm text-green-700 dark:text-green-300">Guardado.</p>
        </div>
    @endif

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre</label>
            <input type="text" id="name" name="name" value="{{ old('name', Auth::user()->name) }}" required autofocus
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror" />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email', Auth::user()->email) }}" required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('email') border-red-500 @enderror" />
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        @if ($mustVerifyEmail && Auth::user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !Auth::user()->hasVerifiedEmail())
            <p class="text-sm text-gray-800 dark:text-gray-200">
                Tu correo no está verificado.
                <form method="POST" action="{{ route('verification.send') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 underline text-sm">
                        Reenviar verificación
                    </button>
                </form>
            </p>
        @endif

        <div class="flex items-center gap-4">
            <button type="submit"
                class="px-4 py-2 rounded-md bg-gray-900 hover:bg-gray-700 text-white text-sm font-medium dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors">
                Guardar
            </button>
        </div>
    </form>
</section>
