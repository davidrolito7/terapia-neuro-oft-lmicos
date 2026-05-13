<x-layouts.app>
    <x-slot:title>Perfil</x-slot:title>

    <x-slot:header>
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Perfil</h2>
    </x-slot:header>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

            <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                @include('profile.partials.update-password-form')
            </div>

            <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-layouts.app>
