@php $editing = $project->exists; @endphp

<x-app-layout>
    <x-slot name="header">{{ $editing ? 'Modifica '.$project->name : 'Nuovo progetto' }}</x-slot>

    <x-resina.payload id="resina-project-groups" :data="$groups" />
    <x-resina.payload id="resina-project-links" :data="$links" />

    <div class="max-w-3xl space-y-4">
        <a href="{{ $editing ? route('resina.projects.show', $project) : route('resina.projects.index') }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> {{ $editing ? $project->name : 'Progetti' }}
        </a>

        <x-resina.form-errors />

        <form method="POST" action="{{ $editing ? route('resina.projects.update', $project) : route('resina.projects.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <x-card class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <x-input-label for="name" value="Nome" />
                        <x-text-input id="name" name="name" value="{{ old('name', $project->name) }}" class="w-full" required />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="subtitle" value="Sottotitolo" />
                        <x-text-input id="subtitle" name="subtitle" value="{{ old('subtitle', $project->subtitle) }}" class="w-full" />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="theme" value="Tema della fascia" />
                        <select id="theme" name="theme" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="">Neutro</option>
                            @foreach ($themes as $theme)
                                <option value="{{ $theme }}" @selected(old('theme', $project->theme) === $theme)>
                                    {{ ['t-ss' => 'Notte stellata (Saint Seiya)', 't-pr' => 'Rosso scuro (Power Rangers)', 't-mv' => 'Blu notte (Marvel)'][$theme] ?? $theme }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="status" value="Stato" />
                        <select id="status" name="status" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="completo" @selected(old('status', $project->status) === 'completo')>Completo</option>
                            <option value="anteprima" @selected(old('status', $project->status) === 'anteprima')>Anteprima</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="armor_label" value="Nome dell'armatura" />
                        <x-text-input id="armor_label" name="armor_label" value="{{ old('armor_label', $project->armor_label) }}" class="w-full" placeholder="Es. Cloth" />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label value="Immagine di copertina (facoltativa)" />
                        <input type="file" name="cover_image" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-xl file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium dark:text-gray-300 dark:file:bg-white/10 dark:file:text-gray-200">
                        @if ($project->cover_image_path)
                            <label class="flex items-center gap-2 text-xs text-gray-500">
                                <input type="checkbox" name="remove_cover_image" value="1" class="rounded-md border-gray-300"> Togli l'immagine attuale
                            </label>
                        @endif
                    </div>
                </div>
                <div class="space-y-1.5">
                    <x-input-label for="intro" value="Introduzione" />
                    <textarea id="intro" name="intro" rows="3" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('intro', $project->intro) }}</textarea>
                </div>
            </x-card>

            <x-card class="space-y-3">
                <x-section-header>Basette predefinite</x-section-header>
                <p class="text-xs text-gray-400">Per i personaggi che non ne hanno di proprie.</p>
                <div class="grid gap-1.5 sm:grid-cols-2">
                    @foreach ($baseRecipes as $recipe)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="default_bases[]" value="{{ $recipe->slug }}" @checked(in_array($recipe->slug, old('default_bases', $project->default_bases ?? []), true))
                                   class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                            {{ $recipe->title }}
                        </label>
                    @endforeach
                </div>
            </x-card>

            <x-card class="space-y-3" x-data="{ open: false }">
                <button type="button" @click="open = ! open" class="flex w-full items-center justify-between">
                    <x-section-header class="mb-0">Ricette in più per «Ricette del progetto»</x-section-header>
                    <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-400 transition" ::class="open && 'rotate-180'" />
                </button>
                <p class="text-xs text-gray-400">Quelle delle zone e delle armature ci sono già: qui aggiungi le altre (effetti, basette…).</p>
                <div x-show="open" x-cloak class="max-h-80 space-y-3 overflow-y-auto">
                    @foreach ($categories as $category)
                        @if ($category->recipes->isNotEmpty())
                            <div>
                                <p class="mb-1 text-xs font-semibold text-gray-500">{{ $category->name }}</p>
                                <div class="grid gap-1.5 sm:grid-cols-2">
                                    @foreach ($category->recipes as $recipe)
                                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" name="extra_recipes[]" value="{{ $recipe->slug }}" @checked(in_array($recipe->slug, old('extra_recipes', $project->extra_recipes ?? []), true))
                                                   class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                                            {{ $recipe->title }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-card>

            <x-card class="space-y-3" x-data="resinaRows('resina-project-groups', { id: '', name: '' })">
                <x-section-header>Gruppi di personaggi</x-section-header>
                <template x-for="(row, index) in rows" :key="row.uid">
                    <div class="flex items-center gap-2">
                        <input type="hidden" :name="`groups[${index}][id]`" :value="row.id">
                        <input type="text" :name="`groups[${index}][name]`" x-model="row.name" required aria-label="Nome gruppo"
                               class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <x-resina.row-buttons />
                    </div>
                </template>
                <button type="button" @click="add()" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300">+ Aggiungi gruppo</button>
            </x-card>

            <x-card class="space-y-3" x-data="resinaRows('resina-project-links', { id: '', title: '', url: '', description: '' })">
                <x-section-header>Riferimenti</x-section-header>
                <template x-for="(row, index) in rows" :key="row.uid">
                    <div class="flex items-start gap-2">
                        <div class="grid min-w-0 flex-1 gap-2 sm:grid-cols-2">
                            <input type="text" :name="`links[${index}][title]`" x-model="row.title" required placeholder="Titolo"
                                   class="rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <input type="url" :name="`links[${index}][url]`" x-model="row.url" required placeholder="https://…"
                                   class="rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <input type="text" :name="`links[${index}][description]`" x-model="row.description" placeholder="Descrizione"
                                   class="rounded-xl border-gray-200 bg-gray-50 text-sm sm:col-span-2 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        </div>
                        <x-resina.row-buttons />
                    </div>
                </template>
                <button type="button" @click="add()" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300">+ Aggiungi riferimento</button>
            </x-card>

            <div class="flex items-center gap-3">
                <x-primary-button>{{ $editing ? 'Salva progetto' : 'Crea progetto' }}</x-primary-button>
                <a href="{{ $editing ? route('resina.projects.show', $project) : route('resina.projects.index') }}" class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400">Annulla</a>
            </div>
        </form>

        @if ($editing)
            <x-resina.delete-card :action="route('resina.projects.destroy', $project)" title="Elimina progetto"
                                  confirm="Eliminare {{ $project->name }} con tutti i suoi personaggi? L'azione non può essere annullata.">
                Cancella il progetto con personaggi, versioni, zone, armature e guide, e l'avanzamento di tutti gli utenti su di loro.
            </x-resina.delete-card>
        @endif
    </div>
</x-app-layout>
