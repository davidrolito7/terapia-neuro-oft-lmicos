<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Actualizar contraseña</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Asegúrate de usar una contraseña larga y aleatoria para mantener tu cuenta segura.
        </p>
    </header>

    @if (session('status') === 'password-updated')
        <div class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 dark:bg-green-900/20 dark:border-green-800">
            <p class="text-sm text-green-700 dark:text-green-300">Guardado.</p>
        </div>
    @endif

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contraseña actual</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('current_password', 'updatePassword') border-red-500 @enderror" />
            @error('current_password', 'updatePassword')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nueva contraseña</label>
            <input type="password" id="password" name="password" autocomplete="new-password"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('password', 'updatePassword') border-red-500 @enderror" />
            @error('password', 'updatePassword')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmar nueva contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('password_confirmation', 'updatePassword') border-red-500 @enderror" />
            @error('password_confirmation', 'updatePassword')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <button type="submit"
                class="px-4 py-2 rounded-md bg-gray-900 hover:bg-gray-700 text-white text-sm font-medium dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors">
                Guardar
            </button>
        </div>
    </form>
</section>
