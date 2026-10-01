@php $editing = $character->exists; @endphp

<x-app-layout>
    <x-slot name="header">{{ $editing ? 'Modifica '.$character->name : 'Nuovo personaggio' }}</x-slot>

    <div class="max-w-3xl space-y-4">
        <a href="{{ $editing ? route('resina.characters.show', [$project, $character]) : route('resina.projects.show', $project) }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> {{ $editing ? $character->name : $project->name }}
        </a>

        <x-resina.form-errors />

        <form method="POST" action="{{ $editing ? route('resina.characters.update', [$project, $character]) : route('resina.characters.store', $project) }}" class="space-y-4">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <x-card class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <x-input-label for="name" value="Nome" />
                        <x-text-input id="name" name="name" value="{{ old('name', $character->name) }}" class="w-full" required />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="character_group_id" value="Gruppo" />
                        <select id="character_group_id" name="character_group_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="">— nessuno —</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}" @selected((int) old('character_group_id', $character->character_group_id) === $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="subtitle" value="Sottotitolo (es. costellazione)" />
                        <x-text-input id="subtitle" name="subtitle" value="{{ old('subtitle', $character->subtitle) }}" class="w-full" />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="alias_it" value="Nome nel doppiaggio italiano" />
                        <x-text-input id="alias_it" name="alias_it" value="{{ old('alias_it', $character->alias_it) }}" class="w-full" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="search_query" value="Ricerca per le reference" />
                    <x-text-input id="search_query" name="search_query" value="{{ old('search_query', $character->search_query) }}" class="w-full" placeholder="Es. Pegasus Seiya" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="versions_note" value="Nota sulle versioni" />
                    <textarea id="versions_note" name="versions_note" rows="2" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('versions_note', $character->versions_note) }}</textarea>
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="tips" value="Consigli (uno per riga)" />
                    <textarea id="tips" name="tips" rows="3" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('tips', implode("\n", $character->tips ?? [])) }}</textarea>
                </div>

                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="hidden" name="no_face" value="0">
                        <input type="checkbox" name="no_face" value="1" @checked(old('no_face', $character->no_face)) class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                        Senza volto (niente occhi e labbra automatici)
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="hidden" name="no_eyes" value="0">
                        <input type="checkbox" name="no_eyes" value="1" @checked(old('no_eyes', $character->no_eyes)) class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                        Senza occhi visibili
                    </label>
                </div>

                <div class="space-y-1.5">
                    <x-input-label value="Basette" />
                    <p class="text-xs text-gray-400">Nessuna spuntata: si usano quelle del progetto.</p>
                    <div class="grid gap-1.5 sm:grid-cols-2">
                        @foreach ($baseRecipes as $recipe)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="bases[]" value="{{ $recipe->slug }}" @checked(in_array($recipe->slug, old('bases', $character->bases ?? []), true))
                                       class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                                {{ $recipe->title }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </x-card>

            <div>
                <x-section-header>Zone comuni a tutte le versioni</x-section-header>
                <p class="mb-3 text-xs text-gray-400">
                    L'ordine delle zone è quello di pittura dentro ogni scheda. Occhi, labbra e basette si aggiungono da soli nella scheda.
                </p>
                <x-resina.zone-editor :payload="$zoneEditor" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>{{ $editing ? 'Salva personaggio' : 'Crea personaggio' }}</x-primary-button>
                <a href="{{ $editing ? route('resina.characters.show', [$project, $character]) : route('resina.projects.show', $project) }}" class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400">Annulla</a>
            </div>
        </form>

        @if ($editing)
            <div>
                <div class="flex items-center justify-between">
                    <x-section-header>Versioni</x-section-header>
                    <a href="{{ route('resina.versions.create', [$project, $character]) }}" class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300">
                        <x-heroicon-o-plus class="h-4 w-4" /> Nuova versione
                    </a>
                </div>
                <x-card class="divide-y divide-gray-100 p-0 dark:divide-white/10">
                    @forelse ($character->versions as $version)
                        <a href="{{ route('resina.versions.edit', [$project, $character, $version]) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-white/5">
                            <span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $version->label }}</span>
                                <span class="text-xs text-gray-500">{{ $version->subtitle }}</span>
                            </span>
                            <span class="text-xs text-gray-400">{{ $character->zones->where('character_version_id', $version->id)->count() }} zone</span>
                        </a>
                    @empty
                        <p class="px-5 py-4 text-sm text-gray-500">Nessuna versione: il personaggio usa solo le zone comuni.</p>
                    @endforelse
                </x-card>
            </div>

            <x-resina.delete-card :action="route('resina.characters.destroy', [$project, $character])" title="Elimina personaggio"
                                  confirm="Eliminare {{ $character->name }}? Si perdono anche le versioni e l'avanzamento di tutti.">
                Cancella il personaggio con zone e versioni, e l'avanzamento di tutti gli utenti su di lui.
            </x-resina.delete-card>
        @endif
    </div>
</x-app-layout>
