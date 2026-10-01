@php $editing = $version->exists; @endphp

<x-app-layout>
    <x-slot name="header">{{ $editing ? $character->name.' · '.$version->label : 'Nuova versione di '.$character->name }}</x-slot>

    <div class="max-w-3xl space-y-4">
        <a href="{{ route('resina.characters.edit', [$project, $character]) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> {{ $character->name }}
        </a>

        <x-resina.form-errors />

        <form method="POST" action="{{ $editing ? route('resina.versions.update', [$project, $character, $version]) : route('resina.versions.store', [$project, $character]) }}" class="space-y-4">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <x-card class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-[1fr_1fr_7rem]">
                    <div class="space-y-1.5">
                        <x-input-label for="label" value="Nome" />
                        <x-text-input id="label" name="label" value="{{ old('label', $version->label) }}" class="w-full" required placeholder="Es. Anime V1" />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="subtitle" value="Sottotitolo" />
                        <x-text-input id="subtitle" name="subtitle" value="{{ old('subtitle', $version->subtitle) }}" class="w-full" placeholder="Es. Santuario" />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="position" value="Posizione" />
                        <x-text-input id="position" name="position" type="number" min="1" value="{{ old('position', $version->position) }}" class="w-full" required />
                    </div>
                </div>
                <div class="space-y-1.5">
                    <x-input-label for="note" value="Nota" />
                    <textarea id="note" name="note" rows="2" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('note', $version->note) }}</textarea>
                </div>
            </x-card>

            <div>
                <x-section-header>Zone di questa versione</x-section-header>
                <p class="mb-3 text-xs text-gray-400">
                    Una zona con lo stesso nome di una zona comune la sostituisce; le altre si aggiungono.
                </p>
                <x-resina.zone-editor :payload="$zoneEditor" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>{{ $editing ? 'Salva versione' : 'Crea versione' }}</x-primary-button>
                <a href="{{ route('resina.characters.edit', [$project, $character]) }}" class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400">Annulla</a>
            </div>
        </form>

        @if ($editing)
            <x-resina.delete-card :action="route('resina.versions.destroy', [$project, $character, $version])" title="Elimina versione"
                                  confirm="Eliminare la versione {{ $version->label }}?">
                Cancella la versione e le sue zone. Chi l'aveva scelta torna alla prima versione.
            </x-resina.delete-card>
        @endif
    </div>
</x-app-layout>
