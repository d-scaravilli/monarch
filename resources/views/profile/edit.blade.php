<x-app-layout>
    <x-slot name="header">{{ __('Profilo') }}</x-slot>

    <div class="max-w-xl space-y-4">
        <a href="{{ route('settings.edit') }}" class="block">
            <x-card class="flex items-center justify-between hover:ring-gray-300 dark:hover:ring-white/20 transition">
                <span class="flex items-center gap-3">
                    <x-heroicon-o-cog-6-tooth class="h-5 w-5 text-gray-400" />
                    <span class="font-medium text-gray-900 dark:text-gray-100">Impostazioni</span>
                </span>
                <x-heroicon-o-chevron-right class="h-5 w-5 text-gray-300" />
            </x-card>
        </a>

        <x-card>
            @include('profile.partials.update-profile-information-form')
        </x-card>

        <x-card>
            @include('profile.partials.update-password-form')
        </x-card>

        <x-card>
            @include('profile.partials.delete-user-form')
        </x-card>

        {{-- Desktop already has logout in the sidebar footer; mobile has no other way out. --}}
        <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
            @csrf
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-gray-100">
                <x-heroicon-o-arrow-right-start-on-rectangle class="h-5 w-5" />
                Esci
            </button>
        </form>
    </div>
</x-app-layout>
