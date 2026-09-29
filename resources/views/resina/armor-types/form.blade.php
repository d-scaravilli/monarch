@php $editing = $armor->exists; @endphp

<x-app-layout>
    <x-slot name="header">{{ $editing ? 'Modifica '.$armor->title : 'Nuova armatura' }}</x-slot>

    <x-resina.payload id="resina-armor-recipes" :data="collect($recipeIds)->map(fn ($id) => ['recipe_id' => (string) $id])->values()" />

    <div class="max-w-3xl space-y-4">
        <a href="{{ route('resina.projects.show', [$project, 'armature']) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> {{ $project->name }} · Armature
        </a>

        <x-resina.form-errors />

        <form method="POST" action="{{ $editing ? route('resina.armor-types.update', [$project, $armor]) : route('resina.armor-types.store', $project) }}" class="space-y-4">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <x-card class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-[1fr_7rem]">
                    <div class="space-y-1.5">
                        <x-input-label for="title" value="Titolo" />
                        <x-text-input id="title" name="title" value="{{ old('title', $armor->title) }}" class="w-full" required />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="position" value="Posizione" />
                        <x-text-input id="position" name="position" type="number" min="1" value="{{ old('position', $armor->position) }}" class="w-full" required />
                    </div>
                </div>
                <div class="space-y-1.5">
                    <x-input-label for="who" value="Chi la indossa" />
                    <x-text-input id="who" name="who" value="{{ old('who', $armor->who) }}" class="w-full" />
                </div>
                <div class="space-y-1.5">
                    <x-input-label for="description" value="Descrizione" />
                    <textarea id="description" name="description" rows="2" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('description', $armor->description) }}</textarea>
                </div>
                <div class="space-y-1.5">
                    <x-input-label for="guide_id" value="Procedura passo passo" />
                    <select id="guide_id" name="guide_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <option value="">— nessuna —</option>
                        @foreach ($guides as $guide)
                            <option value="{{ $guide->id }}" @selected((int) old('guide_id', $armor->guide_id) === $guide->id)>{{ $guide->title }}</option>
                        @endforeach
                    </select>
                </div>
            </x-card>

            <x-card class="space-y-3" x-data="resinaRows('resina-armor-recipes', { recipe_id: '' })">
                <x-section-header>Ricette</x-section-header>
                <template x-for="(row, index) in rows" :key="row.uid">
                    <div class="flex items-center gap-2">
                        <select name="recipes[]" x-model="row.recipe_id" required aria-label="Ricetta"
                                class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="">Scegli una ricetta</option>
                            @foreach ($categories as $category)
                                <optgroup label="{{ $category->name }}">
                                    @foreach ($category->recipes as $recipe)
                                        <option value="{{ $recipe->id }}" :selected="row.recipe_id === '{{ $recipe->id }}'">{{ $recipe->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-resina.row-buttons />
                    </div>
                </template>
                <button type="button" @click="add()" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300">+ Aggiungi ricetta</button>
            </x-card>

            <div class="flex items-center gap-3">
                <x-primary-button>{{ $editing ? 'Salva armatura' : 'Crea armatura' }}</x-primary-button>
                <a href="{{ route('resina.projects.show', [$project, 'armature']) }}" class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400">Annulla</a>
            </div>
        </form>

        @if ($editing)
            <x-resina.delete-card :action="route('resina.armor-types.destroy', [$project, $armor])" title="Elimina armatura" confirm="Eliminare {{ $armor->title }}?">
                Le ricette restano nel Ricettario.
            </x-resina.delete-card>
        @endif
    </div>
</x-app-layout>
