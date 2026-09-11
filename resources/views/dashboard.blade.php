<x-app-layout>
    <x-slot name="header">Moduli</x-slot>

    <div class="flex justify-end mb-4 lg:mb-0 lg:-mt-14 lg:justify-end">
        <a href="{{ route('settings.edit') }}" class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5">
            <x-heroicon-o-cog-6-tooth class="h-5 w-5" />
            Impostazioni
        </a>
    </div>

    @if ($modules->isEmpty())
        <x-card class="text-center text-gray-500 py-10">
            Nessun modulo disponibile per il tuo account.
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modules as $module)
                <a href="{{ route('modules.enter', $module) }}">
                    <x-card class="h-full hover:ring-gray-300 dark:hover:ring-white/20 transition flex items-center gap-4">
                        <x-module-badge :icon="$module->icon" :color="$module->color" size="h-12 w-12" />
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $module->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Apri modulo</p>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
