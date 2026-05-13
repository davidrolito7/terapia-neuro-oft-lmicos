<section class="space-y-6" x-data="{ confirmingDeletion: false }">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Eliminar cuenta</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Una vez que elimines tu cuenta, todos sus recursos y datos serán borrados permanentemente.
        </p>
    </header>

    <button
        @click="confirmingDeletion = true"
        class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-500 text-white text-sm font-medium transition-colors"
    >
        Eliminar cuenta
    </button>

    <!-- Modal de confirmación -->
    <div
        x-show="confirmingDeletion"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="display: none;"
    >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="confirmingDeletion = false"></div>

        <!-- Modal -->
        <div class="relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">¿Eliminar tu cuenta?</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Confirma tu contraseña para eliminar permanentemente tu cuenta.
            </p>

            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="mb-4">
                    <label for="delete_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contraseña</label>
                    <input
                        type="password"
                        id="delete_password"
                        name="password"
                        placeholder="Contraseña"
                        x-ref="deletePasswordInput"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('password', 'userDeletion') border-red-500 @enderror"
                    />
                    @error('password', 'userDeletion')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        @click="confirmingDeletion = false"
                        class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-500 text-white text-sm font-medium transition-colors"
                    >
                        Eliminar cuenta
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
