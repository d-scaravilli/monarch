<x-app-layout>
    <x-slot name="header">Sale</x-slot>

    <a href="{{ route('modules.settings.edit', $currentModule) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center gap-1">
        <x-heroicon-o-chevron-right class="h-3 w-3 rotate-180" />
        Gestisci
    </a>

    <div class="space-y-4 mt-2">
        <x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10">
            @foreach ($rooms as $room)
                <div class="px-5 py-4" x-data="{ editing: false }">
                    <div class="flex items-center justify-between" x-show="!editing">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $room->name }}</p>
                            <p class="text-xs text-gray-400">{{ $room->capacity }} posti &middot; {{ $room->courses_count }} {{ $room->courses_count === 1 ? 'corso' : 'corsi' }}</p>
                        </div>
                        <button type="button" @click="editing = true" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                            <x-heroicon-o-pencil class="h-4 w-4" />
                        </button>
                    </div>

                    <form method="POST" action="{{ route('rooms.update', $room) }}" x-show="editing" x-cloak class="flex flex-wrap items-end gap-3">
                        @csrf @method('PUT')
                        <div class="flex-1 min-w-[8rem] space-y-1.5">
                            <x-input-label value="Nome" />
                            <x-text-input name="name" value="{{ $room->name }}" class="w-full" />
                        </div>
                        <div class="w-28 space-y-1.5">
                            <x-input-label value="Capienza" />
                            <x-text-input type="number" min="1" name="capacity" value="{{ $room->capacity }}" class="w-full" />
                        </div>
                        <x-primary-button>Salva</x-primary-button>
                        <button type="button" @click="editing = false" class="text-sm font-medium text-gray-500 hover:text-gray-700 pb-2.5">Annulla</button>
                    </form>
                </div>
            @endforeach
        </x-card>

        <div>
            <x-section-header>Nuova sala</x-section-header>
            <x-card>
                <form method="POST" action="{{ route('rooms.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[8rem] space-y-1.5">
                        <x-input-label value="Nome" />
                        <x-text-input name="name" class="w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div class="w-28 space-y-1.5">
                        <x-input-label value="Capienza" />
                        <x-text-input type="number" min="1" name="capacity" class="w-full" />
                        <x-input-error :messages="$errors->get('capacity')" class="mt-1" />
                    </div>
                    <x-primary-button>Aggiungi</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
